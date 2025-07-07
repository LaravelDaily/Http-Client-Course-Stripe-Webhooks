<?php

namespace App\Services;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Support\Facades\Log;

class StripeWebhookService
{
    private string $webhookSecret;

    public function __construct()
    {
        $this->webhookSecret = config('services.stripe.webhook_secret');
    }

    public function verifySignature(string $payload, ?string $signature): bool
    {
        if (!$signature || !$this->webhookSecret) {
            return false;
        }

        $elements = explode(',', $signature);
        $signatureData = [];

        foreach ($elements as $element) {
            [$key, $value] = explode('=', $element, 2);
            $signatureData[$key] = $value;
        }

        if (!isset($signatureData['t']) || !isset($signatureData['v1'])) {
            return false;
        }

        $timestamp = $signatureData['t'];
        $signature = $signatureData['v1'];

        // Prevent replay attacks (webhook older than 5 minutes)
        if (abs(time() - $timestamp) > 300) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $timestamp . '.' . $payload, $this->webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }

    public function processEvent(array $event): array
    {
        $eventType = $event['type'];
        $paymentIntent = $event['data']['object'];
        
        // Find order by metadata
        $orderId = $paymentIntent['metadata']['order_id'] ?? null;
        if (!$orderId) {
            Log::warning('Stripe webhook missing order_id metadata', [
                'event_id' => $event['id'],
                'payment_intent_id' => $paymentIntent['id']
            ]);
            return ['processed' => false, 'error' => 'Missing order_id metadata'];
        }

        $order = Order::where('stripe_order_id', $orderId)->first();
        if (!$order) {
            Log::error('Order not found for Stripe webhook', [
                'order_id' => $orderId,
                'event_id' => $event['id']
            ]);
            return ['processed' => false, 'error' => 'Order not found'];
        }

        $orderService = app(OrderService::class);

        try {
            match ($eventType) {
                'payment_intent.succeeded' => $orderService->confirmPayment($order, $paymentIntent),
                'payment_intent.failed' => $orderService->markPaymentFailed($order, $paymentIntent),
                'payment_intent.canceled' => $orderService->cancelOrder($order, $paymentIntent),
                default => null
            };

            return ['processed' => true];

        } catch (\InvalidArgumentException $e) {
            // Bad data from Stripe (shouldn't retry)
            Log::error('Invalid webhook data', [
                'event_id' => $event['id'],
                'error' => $e->getMessage()
            ]);
            return ['processed' => false, 'error' => $e->getMessage()];
            
        } catch (\Exception $e) {
            // Server errors (Stripe will retry)
            Log::error('Webhook processing failed', [
                'event_id' => $event['id'],
                'error' => $e->getMessage()
            ]);
            throw $e; // Re-throw to return 500 status
        }
    }
} 
<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function confirmPayment(Order $order, array $paymentIntent): void
    {
        if ($order->status === 'completed') {
            Log::info('Order already completed', ['order_id' => $order->id]);
            return; // Prevent duplicate processing
        }

        $order->update([
            'status' => 'completed',
            'stripe_payment_intent_id' => $paymentIntent['id'],
            'paid_at' => now(),
            'payment_details' => [
                'amount_received' => $paymentIntent['amount_received'],
                'charges' => $paymentIntent['charges']['data'] ?? []
            ]
        ]);

        // Generate ticket numbers
        $this->generateTickets($order);

        Log::info('Payment confirmed and tickets generated', [
            'order_id' => $order->id,
            'payment_intent_id' => $paymentIntent['id']
        ]);
    }

    public function markPaymentFailed(Order $order, array $paymentIntent): void
    {
        $order->update([
            'status' => 'payment_failed',
            'stripe_payment_intent_id' => $paymentIntent['id'],
            'failure_reason' => $this->extractFailureReason($paymentIntent)
        ]);

        Log::warning('Payment failed', [
            'order_id' => $order->id,
            'failure_reason' => $order->failure_reason
        ]);
    }

    public function cancelOrder(Order $order, array $paymentIntent): void
    {
        $order->update([
            'status' => 'canceled',
            'stripe_payment_intent_id' => $paymentIntent['id'],
            'canceled_at' => now()
        ]);

        Log::info('Order canceled', ['order_id' => $order->id]);
    }

    private function generateTickets(Order $order): void
    {
        // Generate unique ticket codes for water park entry
        $tickets = [];
        for ($i = 0; $i < $order->ticket_quantity; $i++) {
            $tickets[] = [
                'ticket_code' => 'WP-' . strtoupper(uniqid()),
                'valid_date' => $order->visit_date,
                'ticket_type' => $order->ticket_type
            ];
        }

        $order->update(['tickets' => $tickets]);
    }

    private function extractFailureReason(array $paymentIntent): ?string
    {
        $lastCharge = $paymentIntent['charges']['data'][0] ?? null;
        return $lastCharge['failure_message'] ?? 
               $lastCharge['outcome']['seller_message'] ?? 
               'Payment declined';
    }
} 
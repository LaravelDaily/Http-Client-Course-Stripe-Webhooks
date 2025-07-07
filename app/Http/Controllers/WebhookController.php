<?php

namespace App\Http\Controllers;

use App\Services\StripeWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handleStripe(Request $request, StripeWebhookService $webhookService): Response
    {
        try {
            // Verify webhook signature first
            $payload = $request->getContent();
            $signature = $request->header('Stripe-Signature');
            
            if (!$webhookService->verifySignature($payload, $signature)) {
                Log::warning('Invalid Stripe webhook signature', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
                return response('Invalid signature', 400);
            }

            // Process the webhook event
            $event = json_decode($payload, true);
            $result = $webhookService->processEvent($event);

            if ($result['processed']) {
                return response('Webhook processed successfully', 200);
            } else {
                Log::info('Webhook event ignored', [
                    'event_type' => $event['type'],
                    'event_id' => $event['id']
                ]);
                return response('Event type not handled', 200);
            }

        } catch (\Exception $e) {
            Log::error('Stripe webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->getContent()
            ]);
            return response('Webhook processing failed', 500);
        }
    }
}

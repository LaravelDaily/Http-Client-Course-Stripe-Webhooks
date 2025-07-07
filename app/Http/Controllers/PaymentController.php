<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function showForm()
    {
        $orderId = uniqid('waterpark_');
        $error = null;
        $paymentData = null;

        try {
            $clientSecret = $this->paymentService->createPaymentIntent(5000, 'usd', $orderId);
            
            // Create order record
            $order = Order::create([
                'stripe_order_id' => $orderId,
                'customer_email' => 'customer@example.com', // This should come from a form
                'amount' => 5000,
                'currency' => 'usd',
                'status' => 'pending',
                'ticket_quantity' => 1,
                'ticket_type' => 'general',
                'visit_date' => now()->addDays(7),
            ]);
            
            $paymentData = [
                'client_secret' => $clientSecret,
                'publishable_key' => config('services.stripe.public'),
                'amount' => 50.00,
                'order_id' => $orderId,
            ];            
        } catch (\Exception $e) {
            Log::error('Failed to create payment intent', ['error' => $e->getMessage(), 'order_id' => $orderId]);
            $error = $e->getMessage();
        }

        return view('payment.form', array_merge($paymentData ?? [], [
            'error' => $error,
        ]));
    }

    public function success(Request $request)
    {
        $paymentIntentId = $request->query('payment_intent');
        $orderId = $request->query('order_id');

        // Find the order
        $order = Order::where('stripe_order_id', $orderId)->first();

        if (!$order) {
            return view('payment.error', ['message' => 'Order not found']);
        }

        // Check if payment is already confirmed
        if ($order->isCompleted()) {
            return view('payment.success', [
                'order' => $order,
                'message' => 'Payment confirmed! Your tickets have been generated.'
            ]);
        }

        // Payment confirmation will be handled by webhook
        return view('payment.success', [
            'order' => $order,
            'message' => 'Payment is being processed. You will receive an email confirmation shortly.'
        ]);
    }

    public function canceled(Request $request)
    {
        $paymentIntentId = $request->query('payment_intent');
        $orderId = $request->query('order_id');

        // Find the order
        $order = Order::where('stripe_order_id', $orderId)->first();

        if ($order) {
            $order->update(['status' => 'canceled', 'canceled_at' => now()]);
        }

        return view('payment.canceled', [
            'order' => $order,
            'message' => 'Payment was canceled. You can try again anytime.'
        ]);
    }
}

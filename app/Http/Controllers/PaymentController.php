<?php

namespace App\Http\Controllers;

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
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    private $secretKey;
    private $stripeClient;
    
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $this->secretKey = config('services.stripe.secret');
        $this->stripeClient = new \Stripe\StripeClient($this->secretKey);
    }
    
    public function createPaymentIntent($amount, $currency = 'usd', $orderId = null): string
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->secretKey,
        ];
        
        $response = Http::withHeaders($headers)
            ->post('https://api.stripe.com/v1/payment_intents', [
                'amount' => $amount, 
                'currency' => $currency,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'order_id' => $orderId ?? uniqid('waterpark_'),
                    'product' => 'Water Park Ticket',
                ],
            ]);
        
        if ($response->successful()) {
            return $response->json()['client_secret'];
        }
            
        $this->handleApiError($response);            
    }
    
    public function createPaymentIntentWithPackage($amount, $currency = 'usd', $orderId = null): string
    {
        try {
            $params = [
                'amount' => $amount, // Amount in cents
                'currency' => $currency,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'order_id' => $orderId ?? uniqid(),
                    'product' => 'Water Park Ticket',
                ],
            ];
            
            $paymentIntent = $this->stripeClient->paymentIntents->create($params);
            
            return $paymentIntent->client_secret;
        } catch (\Stripe\Exception\CardException $e) {
            // Card was declined
            throw new \Exception($e->getDeclineCode() ?? 'Payment declined');
        } catch (\Stripe\Exception\RateLimitException $e) {
            // Too many requests made to the API too quickly
            throw new \Exception('Rate limit exceeded');
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Invalid parameters were supplied to Stripe's API
            throw new \InvalidArgumentException($e->getMessage());
        } catch (\Stripe\Exception\AuthenticationException $e) {
            // Authentication with Stripe's API failed
            throw new \Exception('Invalid Stripe API key');
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            // Network communication with Stripe failed
            throw new \Exception('Network communication with Stripe failed');
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Generic API error
            throw new \Exception('Stripe API error: ' . $e->getMessage());
        }
    }
        
    private function handleApiError($response)
    {
        $statusCode = $response->status();
        $errorData = $response->json();
        
        switch ($statusCode) {
            case 400:
                throw new \InvalidArgumentException(
                    $errorData['error']['message'] ?? 'Invalid request parameters'
                );
                
            case 401:
                throw new \Exception('Invalid Stripe API key');
                
            case 402:
                throw new \Exception(
                    $errorData['error']['decline_code'] ?? 'Payment declined'
                );
                
            case 403:
                throw new \Exception('Insufficient API permissions');
                
            case 429:
                throw new \Exception('Rate limit exceeded');
                
            default:
                throw new \Exception('Stripe API error: ' . $response->body());
        }
    }
}

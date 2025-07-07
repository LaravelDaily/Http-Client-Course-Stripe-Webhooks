<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Payment routes
Route::get('/payment', [PaymentController::class, 'showForm'])->name('payment.form');

// Webhook routes
Route::post('/webhooks/stripe', [WebhookController::class, 'handleStripe'])->name('webhooks.stripe');
Route::get('/payment/success', [PaymentController::class, 'success'])->name('payment.success');
Route::get('/payment/canceled', [PaymentController::class, 'canceled'])->name('payment.canceled');

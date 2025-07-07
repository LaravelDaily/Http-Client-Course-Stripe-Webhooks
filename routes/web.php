<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Payment routes
Route::get('/payment', [PaymentController::class, 'showForm'])->name('payment.form');

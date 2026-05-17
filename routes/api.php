<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('products', [ProductController::class, 'index'])->middleware('throttle:api');
Route::get('products/autocomplete', [ProductController::class, 'autocomplete'])->middleware('throttle:api');
Route::get('products/{slug}', [ProductController::class, 'show'])->middleware('throttle:api');
Route::get('categories', [ProductController::class, 'categories'])->middleware('throttle:api');

Route::get('settings', [SettingsController::class, 'index']);

// Payment routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('payments/create-session', [PaymentController::class, 'createCheckoutSession']);
    Route::post('payments/{order}/retry', [PaymentController::class, 'retryPayment']);
    Route::get('payments/success', [PaymentController::class, 'confirmSuccess']);
});

// Webhook (no auth required)
Route::post('payments/webhook', [PaymentController::class, 'handleWebhook']);

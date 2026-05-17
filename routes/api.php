<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('products', [ProductController::class, 'index'])->middleware('throttle:api');
Route::get('products/{slug}', [ProductController::class, 'show'])->middleware('throttle:api');
Route::get('categories', [ProductController::class, 'categories'])->middleware('throttle:api');

Route::get('settings', [SettingsController::class, 'index']);

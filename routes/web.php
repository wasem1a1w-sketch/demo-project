<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController as ApiOrderController;
use App\Http\Controllers\Api\ProductReviewController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController as ShopOrderController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('web')->match(['get', 'post'], '/broadcasting/auth', [
    \Illuminate\Broadcasting\BroadcastController::class, 'authenticate'
])->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

Route::middleware('web')->group(function () {
    // Shop routes
    Route::get('/', [ShopController::class, 'index'])->name('home');
    Route::get('/shop', [ShopController::class, 'shop'])->name('shop');
    Route::get('/product/{slug}', [ShopController::class, 'product'])->name('product');
    Route::get('/cart', [ShopController::class, 'cart'])->name('cart');
    Route::get('/checkout', [ShopController::class, 'checkout'])->name('checkout');
    Route::get('/categories', [ShopController::class, 'categories'])->name('categories');
    Route::get('/orders/{orderNumber}', [ShopOrderController::class, 'show'])->name('orders.show');

    // User routes (authenticated)
    Route::middleware('auth')->group(function () {
        Route::get('/orders', [ShopOrderController::class, 'index'])->name('orders.index');
        Route::get('/addresses', [AddressController::class, 'index'])->name('addresses.index');
        Route::post('/addresses', [AddressController::class, 'store'])->name('addresses.store');
        Route::put('/addresses/{id}', [AddressController::class, 'update'])->name('addresses.update');
        Route::delete('/addresses/{id}', [AddressController::class, 'destroy'])->name('addresses.destroy');
        Route::get('/wishlist', [ShopController::class, 'wishlist'])->name('wishlist');

        // Notification routes
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread', [NotificationController::class, 'unread']);
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    });

    // API Cart routes (session-based)
    Route::prefix('api')->group(function () {
        Route::get('cart', [CartController::class, 'index']);
        Route::post('cart/add', [CartController::class, 'add']);
        Route::delete('cart/clear', [CartController::class, 'clear']);
        Route::post('cart/coupon', [CartController::class, 'applyCoupon']);
        Route::delete('cart/coupon', [CartController::class, 'removeCoupon']);
        Route::patch('cart/{id}', [CartController::class, 'update']);
        Route::delete('cart/{id}', [CartController::class, 'remove']);
        Route::post('orders', [ApiOrderController::class, 'store']);
        Route::get('orders/{orderNumber}', [ApiOrderController::class, 'show']);

        // Public: anyone can view approved reviews
        Route::get('products/{product}/reviews', [ProductReviewController::class, 'index']);
    });

    // API Product Reviews (authenticated)
    Route::middleware('auth')->prefix('api')->group(function () {
        Route::post('products/{product}/reviews', [ProductReviewController::class, 'store']);
        Route::put('products/{product}/reviews/{review}', [ProductReviewController::class, 'update']);
        Route::delete('products/{product}/reviews/{review}', [ProductReviewController::class, 'destroy']);
    });

    // API Wishlist routes (authenticated)
    Route::middleware('auth')->prefix('api')->group(function () {
        Route::get('wishlist', [WishlistController::class, 'index']);
        Route::post('wishlist/add', [WishlistController::class, 'add']);
        Route::delete('wishlist/{id}', [WishlistController::class, 'remove']);
        Route::delete('wishlist', [WishlistController::class, 'clear']);
    });

    // Auth routes
    Route::get('/login', fn () => Inertia::render('Auth/Login'))->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', fn () => Inertia::render('Auth/Register'))->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Password Reset
    Route::middleware('guest')->group(function () {
        Route::get('/forgot-password', [PasswordResetController::class, 'forgotPassword'])->name('password.request');
        Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
        Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetPasswordForm'])->name('password.reset.form');
        Route::post('/reset-password', [PasswordResetController::class, 'updatePassword'])->name('password.update');
    });

    // Email Verification
    Route::middleware('auth')->group(function () {
        Route::get('/email/verify', [VerificationController::class, 'showNotice'])->name('verification.notice');
        Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
        Route::post('/email/resend', [VerificationController::class, 'resend'])->name('verification.resend');
    });
});

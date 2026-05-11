<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductImageController as AdminProductImageController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController as ApiOrderController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController as ShopOrderController;
use App\Http\Controllers\ShopController;
use App\Http\Middleware\AdminOnly;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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
    });

    // API Cart routes (session-based)
    Route::prefix('api')->group(function () {
        Route::get('cart', [CartController::class, 'index']);
        Route::post('cart/add', [CartController::class, 'add']);
        Route::patch('cart/{id}', [CartController::class, 'update']);
        Route::delete('cart/{id}', [CartController::class, 'remove']);
        Route::delete('cart/clear', [CartController::class, 'clear']);
        Route::post('cart/coupon', [CartController::class, 'applyCoupon']);
        Route::delete('cart/coupon', [CartController::class, 'removeCoupon']);
        Route::post('orders', [ApiOrderController::class, 'store']);
        Route::get('orders/{orderNumber}', [ApiOrderController::class, 'show']);
    });

    // Auth routes
    Route::get('/login', fn () => Inertia::render('Auth/Login'))->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', fn () => Inertia::render('Auth/Register'))->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Admin routes - protected
    Route::middleware(['auth', AdminOnly::class])->group(function () {
        Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');
        Route::get('/admin/products', [AdminProductController::class, 'index'])->name('admin.products');
        Route::get('/admin/products/create', [AdminProductController::class, 'create'])->name('admin.products.create');
        Route::post('/admin/products', [AdminProductController::class, 'store'])->name('admin.products.store');
        Route::get('/admin/products/{id}/edit', [AdminProductController::class, 'edit'])->name('admin.products.edit');
        Route::post('/admin/products/{id}', [AdminProductController::class, 'update'])->name('admin.products.update');
        Route::delete('/admin/products/{id}', [AdminProductController::class, 'destroy'])->name('admin.products.destroy');
        Route::delete('/admin/product-images/{id}', [AdminProductImageController::class, 'destroy'])->name('admin.product-images.destroy');

        Route::get('/admin/orders', [AdminOrderController::class, 'index'])->name('admin.orders');
        Route::get('/admin/orders/{id}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
        Route::put('/admin/orders/{id}', [AdminOrderController::class, 'update'])->name('admin.orders.update');

        Route::get('/admin/categories', [AdminCategoryController::class, 'index'])->name('admin.categories');
        Route::post('/admin/categories', [AdminCategoryController::class, 'store'])->name('admin.categories.store');
        Route::post('/admin/categories/{id}', [AdminCategoryController::class, 'update'])->name('admin.categories.update');
        Route::delete('/admin/categories/{id}', [AdminCategoryController::class, 'destroy'])->name('admin.categories.destroy');
    });
});

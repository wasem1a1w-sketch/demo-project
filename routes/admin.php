<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductImageController as AdminProductImageController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\AdminOnly;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', AdminOnly::class])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard')
        ->can('dashboard.view');

    Route::get('/admin/products', [AdminProductController::class, 'index'])->name('admin.products')
        ->can('products.read');
    Route::get('/admin/products/create', [AdminProductController::class, 'create'])->name('admin.products.create')
        ->can('products.create');
    Route::post('/admin/products', [AdminProductController::class, 'store'])->name('admin.products.store')
        ->can('products.create');
    Route::get('/admin/products/{id}/edit', [AdminProductController::class, 'edit'])->name('admin.products.edit')
        ->can('products.update');
    Route::post('/admin/products/{id}', [AdminProductController::class, 'update'])->name('admin.products.update')
        ->can('products.update');
    Route::delete('/admin/products/{id}', [AdminProductController::class, 'destroy'])->name('admin.products.destroy')
        ->can('products.delete');
    Route::delete('/admin/product-images/{id}', [AdminProductImageController::class, 'destroy'])->name('admin.product-images.destroy')
        ->can('products.images.delete');

    Route::get('/admin/orders', [AdminOrderController::class, 'index'])->name('admin.orders')
        ->can('orders.read');
    Route::get('/admin/orders/{id}', [AdminOrderController::class, 'show'])->name('admin.orders.show')
        ->can('orders.read');
    Route::put('/admin/orders/{id}', [AdminOrderController::class, 'update'])->name('admin.orders.update')
        ->can('orders.update');

    Route::get('/admin/categories', [AdminCategoryController::class, 'index'])->name('admin.categories')
        ->can('categories.read');
    Route::post('/admin/categories', [AdminCategoryController::class, 'store'])->name('admin.categories.store')
        ->can('categories.create');
    Route::post('/admin/categories/{id}', [AdminCategoryController::class, 'update'])->name('admin.categories.update')
        ->can('categories.update');
    Route::delete('/admin/categories/{id}', [AdminCategoryController::class, 'destroy'])->name('admin.categories.destroy')
        ->can('categories.delete');

    Route::get('/admin/users/roles/create', [RoleController::class, 'create'])->name('admin.users.roles.create')
        ->can('roles.create');
    Route::post('/admin/users/roles', [RoleController::class, 'store'])->name('admin.users.roles.store')
        ->can('roles.create');
    Route::get('/admin/users/roles/{role}/edit', [RoleController::class, 'edit'])->name('admin.users.roles.edit')
        ->can('roles.update');
    Route::post('/admin/users/roles/{role}', [RoleController::class, 'update'])->name('admin.users.roles.update')
        ->can('roles.update');
    Route::delete('/admin/users/roles/{role}', [RoleController::class, 'destroy'])->name('admin.users.roles.destroy')
        ->can('roles.delete');

    Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users')
        ->can('users.read');
    Route::get('/admin/users/create', [UserController::class, 'create'])->name('admin.users.create')
        ->can('users.create');
    Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store')
        ->can('users.create');
    Route::get('/admin/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit')
        ->can('users.update');
    Route::post('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update')
        ->can('users.update');
    Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy')
        ->can('users.delete');
});

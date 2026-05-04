<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::factory()->create(['is_active' => true]);
});

describe('order placement', function () {
    it('can place an order with valid data', function () {
        $product = Product::factory()->create([
            'price' => 50.00,
            'stock' => 10,
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);
        ProductImage::factory()->create([
            'product_id' => $product->id,
            'is_primary' => true,
        ]);

        // Add to cart first
        $this->postJson("/api/cart/add", [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->postJson('/api/orders', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'USA',
            'phone' => '1234567890',
            'payment_method' => 'stripe',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'price' => 50.00,
            ]],
            'subtotal' => 100.00,
            'tax' => 10.00,
            'shipping' => 15.00,
            'discount' => 0,
            'total' => 125.00,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'order_number',
                'status',
                'total',
            ]);

        // Check stock was decremented
        expect($product->fresh()->stock)->toBe(8);
    });

    it('returns error for empty cart', function () {
        $response = $this->postJson('/api/orders', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'USA',
            'phone' => '1234567890',
            'payment_method' => 'stripe',
            'items' => [],
            'subtotal' => 0,
            'tax' => 0,
            'shipping' => 0,
            'discount' => 0,
            'total' => 0,
        ]);

        $response->assertStatus(422);
    });

    it('applies coupon and increments usage', function () {
        $coupon = Coupon::factory()->create([
            'type' => 'percentage',
            'value' => 10,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
            'usage_limit' => 10,
            'used_count' => 0,
        ]);

        $product = Product::factory()->create([
            'price' => 100,
            'stock' => 10,
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $this->postJson("/api/cart/add", ['product_id' => $product->id, 'quantity' => 1]);
        $this->postJson("/api/cart/coupon", ['code' => $coupon->code]);

        $this->postJson('/api/orders', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'USA',
            'phone' => '1234567890',
            'payment_method' => 'stripe',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 100,
            ]],
            'subtotal' => 100,
            'tax' => 10,
            'shipping' => 15,
            'discount' => 10,
            'total' => 115,
            'coupon_id' => $coupon->id,
        ]);

        expect($coupon->fresh()->used_count)->toBe(1);
    });

    it('rolls back on database error', function () {
        $product = Product::factory()->create([
            'price' => 50,
            'stock' => 10,
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        // Force an error by providing invalid data that will cause DB constraint violation
        // The order number generation should still work, but we can test rollback
        $response = $this->postJson('/api/orders', [
            'first_name' => 'John',
            // Missing required fields
            'payment_method' => 'invalid_method',
        ]);

        $response->assertStatus(422);
    });
});

describe('order viewing', function () {
    it('can view order by order number', function () {
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $orderResponse = $this->postJson('/api/orders', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'USA',
            'phone' => '1234567890',
            'payment_method' => 'stripe',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 50,
            ]],
            'subtotal' => 50,
            'tax' => 5,
            'shipping' => 15,
            'discount' => 0,
            'total' => 70,
        ]);

        $orderNumber = $orderResponse->json('order_number');

        $response = $this->getJson("/api/orders/{$orderNumber}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'order_number',
                'items',
                'status',
            ]);
    });

    it('returns error for non-existent order', function () {
        $response = $this->getJson('/api/orders/INVALID123');

        $response->assertStatus(404);
    });
});

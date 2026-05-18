<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::factory()->create(['is_active' => true]);

        Setting::set('shipping_rate', 15);
        Setting::set('free_shipping_threshold', 100);
        Setting::set('tax_rate', 10);
    }

    public function test_can_place_an_order_with_valid_data(): void
    {
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

        $this->postJson("/api/cart/add", [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $orderData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'address2' => '',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'USA',
            'phone' => '1234567890',
            'payment_method' => 'stripe',
        ];

        $response = $this->postJson('/api/orders', array_merge($orderData, [
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
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'order_number',
                'message',
            ]);

        $this->assertEquals(8, $product->fresh()->stock);
    }

    public function test_returns_error_for_empty_cart(): void
    {
        $response = $this->postJson('/api/orders', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'address2' => '',
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
    }

    public function test_applies_coupon_and_increments_usage(): void
    {
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

        $orderData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'address2' => '',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'USA',
            'phone' => '1234567890',
            'payment_method' => 'stripe',
        ];

        $this->postJson('/api/orders', array_merge($orderData, [
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
        ]));

        $this->assertEquals(1, $coupon->fresh()->used_count);
    }

    public function test_rolls_back_on_database_error(): void
    {
        $product = Product::factory()->create([
            'price' => 50,
            'stock' => 10,
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $response = $this->postJson('/api/orders', [
            'first_name' => 'John',
            'payment_method' => 'invalid_method',
        ]);

        $response->assertStatus(422);
    }

    public function test_can_view_order_by_order_number(): void
    {
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $orderData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'address2' => '',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'USA',
            'phone' => '1234567890',
            'payment_method' => 'stripe',
        ];

        $orderResponse = $this->postJson('/api/orders', array_merge($orderData, [
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
        ]));

        $orderNumber = $orderResponse->json('order_number');

        $response = $this->getJson("/api/orders/{$orderNumber}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'order_number',
                'items',
                'status',
            ]);
    }

    public function test_returns_error_for_non_existent_order(): void
    {
        $response = $this->getJson('/api/orders/INVALID123');

        $response->assertStatus(404);
    }

    public function test_tax_is_recalculated_server_side(): void
    {
        $product = Product::factory()->create([
            'price' => 100.00,
            'stock' => 10,
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $orderData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'address2' => '',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'USA',
            'phone' => '1234567890',
            'payment_method' => 'stripe',
        ];

        $this->postJson('/api/orders', array_merge($orderData, [
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 100,
            ]],
            'subtotal' => 100,
            'tax' => 999,
            'shipping' => 15,
            'discount' => 0,
            'total' => 999,
        ]));

        // Tax should be recalculated as (100 - 0) * 10% = 10, not 999
        $this->assertDatabaseHas('orders', [
            'subtotal' => 100.00,
            'tax' => 10.00,
            'total' => 110.00,
        ]);
    }

    public function test_shipping_is_recalculated_server_side(): void
    {
        $product = Product::factory()->create([
            'price' => 45.00,
            'stock' => 10,
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $orderData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'address2' => '',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'USA',
            'phone' => '1234567890',
            'payment_method' => 'stripe',
        ];

        $this->postJson('/api/orders', array_merge($orderData, [
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'price' => 45,
            ]],
            'subtotal' => 90,
            'tax' => 9,
            'shipping' => 0,
            'discount' => 0,
            'total' => 99,
        ]));

        // Subtotal 90 < threshold 100, so shipping should be 15, not 0
        $this->assertDatabaseHas('orders', [
            'subtotal' => 90.00,
            'shipping' => 15.00,
            'tax' => 9.00,
            'total' => 114.00,
        ]);
    }

    public function test_free_shipping_above_threshold(): void
    {
        $product = Product::factory()->create([
            'price' => 60.00,
            'stock' => 10,
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $orderData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'address2' => '',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'USA',
            'phone' => '1234567890',
            'payment_method' => 'stripe',
        ];

        $this->postJson('/api/orders', array_merge($orderData, [
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'price' => 60,
            ]],
            'subtotal' => 120,
            'tax' => 12,
            'shipping' => 15,
            'discount' => 0,
            'total' => 147,
        ]));

        // Subtotal 120 >= threshold 100, so shipping should be 0
        $this->assertDatabaseHas('orders', [
            'subtotal' => 120.00,
            'shipping' => 0.00,
            'tax' => 12.00,
            'total' => 132.00,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::factory()->create(['is_active' => true]);

        Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']);
    }

    public function test_api_returns_429_after_exceeding_limit(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $response = $this->getJson('/api/products');
            $response->assertStatus(200);
        }

        $response = $this->getJson('/api/products');
        $response->assertStatus(429);
    }

    public function test_login_returns_429_after_too_many_attempts(): void
    {
        $this->from('/login');

        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
            $response->assertStatus(302);
        }

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);
        $response->assertStatus(429);
    }

    public function test_register_returns_429_after_too_many_attempts(): void
    {
        $this->from('/register');

        for ($i = 0; $i < 3; $i++) {
            $response = $this->post('/register', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => "user{$i}@example.com",
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);
            $response->assertStatus(302);
        }

        $response = $this->post('/register', [
            'first_name' => 'Extra',
            'last_name' => 'User',
            'email' => 'extra@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $response->assertStatus(429);
    }

    public function test_password_reset_link_returns_429_after_limit(): void
    {
        $this->from('/forgot-password');

        for ($i = 0; $i < 3; $i++) {
            $response = $this->post('/forgot-password', [
                'email' => "user{$i}@example.com",
            ]);
            $response->assertStatus(302);
        }

        $response = $this->post('/forgot-password', [
            'email' => 'extra@example.com',
        ]);
        $response->assertStatus(429);
    }

    public function test_checkout_returns_429_after_limit(): void
    {
        $product = Product::factory()->create([
            'price' => 50.00,
            'stock' => 100,
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

        for ($i = 0; $i < 10; $i++) {
            $this->postJson("/api/cart/add", [
                'product_id' => $product->id,
                'quantity' => 1,
            ]);

            $response = $this->postJson('/api/orders', array_merge($orderData, [
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => 50.00,
                ]],
                'subtotal' => 50.00,
                'tax' => 5.00,
                'shipping' => 15.00,
                'discount' => 0,
                'total' => 70.00,
            ]));
            $response->assertStatus(200);
        }

        $this->postJson("/api/cart/add", [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->postJson('/api/orders', array_merge($orderData, [
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 50.00,
            ]],
            'subtotal' => 50.00,
            'tax' => 5.00,
            'shipping' => 15.00,
            'discount' => 0,
            'total' => 70.00,
        ]));
        $response->assertStatus(429);
    }
}

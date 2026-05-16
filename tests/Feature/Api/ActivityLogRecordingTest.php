<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityLogRecordingTest extends TestCase
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

        if (! \Spatie\Permission\Models\Role::where('name', 'client')->exists()) {
            \Spatie\Permission\Models\Role::create(['name' => 'client', 'guard_name' => 'web']);
        }
    }

    public function test_logs_user_registration(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'user_registered',
            'description' => 'User registered: john@example.com',
        ]);
    }

    public function test_logs_user_login(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('Password1!'),
        ]);

        $response = $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'Password1!',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'user_login',
            'user_id' => $user->id,
            'description' => "User logged in: {$user->email}",
        ]);
    }

    public function test_logs_order_placement(): void
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

        $this->postJson('/api/cart/add', [
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

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'order_placed',
            'description' => "Order placed: {$response->json('order_number')}",
        ]);
    }

    public function test_logs_review_submission(): void
    {
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 5,
            'title' => 'Great!',
            'body' => 'Love it',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'review_created',
            'user_id' => $user->id,
            'description' => "Review submitted for product: {$product->name}",
        ]);
    }
}

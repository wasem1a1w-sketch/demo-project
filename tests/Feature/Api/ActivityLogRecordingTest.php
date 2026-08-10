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
use Tests\Support\InteractsWithKafka;
use Tests\TestCase;

class ActivityLogRecordingTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithKafka;

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
        $this->fakeKafka();
        $response = $this->post('/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $response->assertStatus(302);

        $this->drainKafkaMessages();

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'user_registered',
            'description' => 'User registered: john@example.com',
        ]);
    }

    public function test_logs_user_login(): void
    {
        $this->fakeKafka();
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('Password1!'),
        ]);

        $response = $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'Password1!',
        ]);

        $response->assertStatus(302);

        $this->drainKafkaMessages();

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'user_login',
            'user_id' => $user->id,
            'description' => "User logged in: {$user->email}",
        ]);
    }

    public function test_logs_order_placement(): void
    {
        $this->fakeKafka();
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

        $this->drainKafkaMessages();

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'order_placed',
            'description' => "Order placed: {$response->json('order_number')}",
        ]);
    }

    public function test_logs_review_submission(): void
    {
        $this->fakeKafka();
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

        $this->drainKafkaMessages();

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'review_created',
            'user_id' => $user->id,
            'description' => "Review submitted for product: {$product->name}",
        ]);
    }

    public function test_logs_user_logout(): void
    {
        $this->fakeKafka();
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('Password1!'),
        ]);

        $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'Password1!',
        ]);

        $this->post('/logout');

        $this->drainKafkaMessages();

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'user_logout',
            'user_id' => $user->id,
            'description' => "User logged out: {$user->email}",
        ]);
    }

    public function test_logs_address_creation(): void
    {
        $this->fakeKafka();
        $user = User::factory()->create();

        $this->actingAs($user)->post('/addresses', [
            'type' => 'shipping',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'USA',
            'phone' => '1234567890',
        ]);

        $this->drainKafkaMessages();

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'address_created',
            'user_id' => $user->id,
            'description' => 'Address created: 123 Main St, New York',
        ]);
    }

    public function test_logs_cart_item_added(): void
    {
        $this->fakeKafka();
        $product = Product::factory()->create([
            'is_active' => true,
            'stock' => 99,
            'category_id' => $this->category->id,
        ]);

        $this->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->drainKafkaMessages();

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'cart_item_added',
            'description' => "Item added to cart: {$product->name} x2",
        ]);
    }

    public function test_logs_coupon_applied(): void
    {
        $this->fakeKafka();
        $product = Product::factory()->create([
            'is_active' => true,
            'stock' => 99,
            'category_id' => $this->category->id,
        ]);

        $this->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $coupon = Coupon::factory()->create([
            'code' => 'SAVE10',
            'type' => 'percentage',
            'value' => 10,
            'min_order_amount' => 0,
            'usage_limit' => 100,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $this->postJson('/api/cart/coupon', ['code' => 'SAVE10']);

        $this->drainKafkaMessages();

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'coupon_applied',
            'description' => 'Coupon applied: SAVE10',
        ]);
    }

    public function test_logs_wishlist_item_added(): void
    {
        $this->fakeKafka();
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($user)->postJson('/api/wishlist/add', [
            'product_id' => $product->id,
        ]);

        $this->drainKafkaMessages();

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'wishlist_item_added',
            'user_id' => $user->id,
            'description' => "Item added to wishlist: {$product->name}",
        ]);
    }
}

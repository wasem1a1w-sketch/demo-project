<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityLogAdminRecordingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permissions = Permission::pluck('id')->toArray();
        $adminRole->syncPermissions($permissions);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->category = Category::factory()->create(['is_active' => true]);
    }

    private function createOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => Order::generateOrderNumber(),
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 100,
            'tax' => 10,
            'shipping' => 5,
            'total' => 115,
            'shipping_name' => 'Test',
            'shipping_address' => '123 St',
            'shipping_city' => 'City',
            'shipping_state' => 'ST',
            'shipping_postal_code' => '12345',
            'shipping_country' => 'US',
            'shipping_phone' => '555-0000',
            'payment_method' => 'stripe',
        ], $overrides));
    }

    public function test_logs_product_creation(): void
    {
        $this->actingAs($this->admin)->post('/admin/products', [
            'name' => 'New Product',
            'slug' => 'new-product',
            'price' => 29.99,
            'stock' => 10,
            'category_id' => $this->category->id,
            'is_active' => true,
            'is_featured' => false,
        ]);

        $product = Product::where('slug', 'new-product')->first();

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'product_created',
            'user_id' => $this->admin->id,
            'description' => "Product created: New Product",
        ]);
    }

    public function test_logs_product_update(): void
    {
        $product = Product::factory()->create([
            'name' => 'Old Name',
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($this->admin)->post("/admin/products/{$product->id}", [
            'name' => 'Updated Name',
            'slug' => $product->slug,
            'price' => $product->price,
            'stock' => $product->stock,
            'category_id' => $product->category_id,
            'is_active' => true,
            'is_featured' => false,
        ]);

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'product_updated',
            'user_id' => $this->admin->id,
            'description' => "Product updated: Updated Name",
        ]);
    }

    public function test_logs_product_deletion(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($this->admin)->delete("/admin/products/{$product->id}");

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'product_deleted',
            'user_id' => $this->admin->id,
            'description' => "Product deleted: {$product->name}",
        ]);
    }

    public function test_logs_category_creation(): void
    {
        $this->actingAs($this->admin)->post('/admin/categories', [
            'name' => 'New Category',
            'slug' => 'new-category',
        ]);

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'category_created',
            'user_id' => $this->admin->id,
            'description' => 'Category created: New Category',
        ]);
    }

    public function test_logs_category_update(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)->post("/admin/categories/{$category->id}", [
            'name' => 'Updated Category',
            'slug' => $category->slug,
        ]);

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'category_updated',
            'user_id' => $this->admin->id,
            'description' => 'Category updated: Updated Category',
        ]);
    }

    public function test_logs_category_deletion(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)->delete("/admin/categories/{$category->id}");

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'category_deleted',
            'user_id' => $this->admin->id,
            'description' => "Category deleted: {$category->name}",
        ]);
    }

    public function test_logs_order_status_change(): void
    {
        $order = $this->createOrder();

        $this->actingAs($this->admin)->put("/admin/orders/{$order->id}", [
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'order_status_changed',
            'user_id' => $this->admin->id,
            'description' => "Order #{$order->order_number} status changed: pending → processing",
        ]);
    }

    public function test_logs_user_creation(): void
    {
        $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'user_created',
            'user_id' => $this->admin->id,
            'description' => 'User created: newuser@example.com',
        ]);
    }

    public function test_logs_user_update(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);

        $this->actingAs($this->admin)->post("/admin/users/{$user->id}", [
            'name' => 'Updated User',
            'email' => 'updated@example.com',
        ]);

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'user_updated',
            'user_id' => $this->admin->id,
            'description' => 'User updated: updated@example.com',
        ]);
    }

    public function test_logs_user_deletion(): void
    {
        $user = User::factory()->create(['email' => 'delete@example.com']);

        $this->actingAs($this->admin)->delete("/admin/users/{$user->id}");

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'user_deleted',
            'user_id' => $this->admin->id,
            'description' => 'User deleted: delete@example.com',
        ]);
    }

    public function test_logs_review_approval(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);
        $review = ProductReview::create([
            'product_id' => $product->id,
            'user_id' => User::factory()->create()->id,
            'rating' => 5,
            'title' => 'Test',
            'is_approved' => false,
        ]);

        $this->actingAs($this->admin)->patch("/admin/reviews/{$review->id}/approve");

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'review_approved',
            'user_id' => $this->admin->id,
            'description' => "Review #{$review->id} approved",
        ]);
    }

    public function test_logs_review_rejection(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);
        $review = ProductReview::create([
            'product_id' => $product->id,
            'user_id' => User::factory()->create()->id,
            'rating' => 5,
            'title' => 'Test',
            'is_approved' => true,
        ]);

        $this->actingAs($this->admin)->patch("/admin/reviews/{$review->id}/reject");

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'review_rejected',
            'user_id' => $this->admin->id,
            'description' => "Review #{$review->id} rejected",
        ]);
    }

    public function test_logs_review_deletion(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);
        $review = ProductReview::create([
            'product_id' => $product->id,
            'user_id' => User::factory()->create()->id,
            'rating' => 5,
            'title' => 'Test',
        ]);

        $this->actingAs($this->admin)->delete("/admin/reviews/{$review->id}");

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'review_deleted',
            'user_id' => $this->admin->id,
            'description' => "Review #{$review->id} deleted",
        ]);
    }

    public function test_logs_settings_update(): void
    {
        $this->actingAs($this->admin)->put('/admin/settings', [
            'shipping_rate' => 20,
            'tax_rate' => 8,
            'free_shipping_threshold' => 150,
        ]);

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'settings_updated',
            'user_id' => $this->admin->id,
            'description' => 'Settings updated',
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithKafka;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithKafka;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeKafka();
        $this->category = Category::factory()->create(['is_active' => true]);
    }

    public function test_user_can_place_order(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 25.00,
            'stock' => 10,
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'address2' => '',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'US',
            'phone' => '+1234567890',
            'payment_method' => 'stripe',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 25.00]],
            'subtotal' => 50.00,
            'tax' => 4.00,
            'shipping' => 0,
            'discount' => 0,
            'total' => 54.00,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['order_id', 'order_number', 'message']);
        $this->assertDatabaseHas('orders', [
            'id' => $response->json('order_id'),
            'user_id' => $user->id,
        ]);
        $this->assertEquals(8, $product->fresh()->stock);
    }

    public function test_order_number_is_generated(): void
    {
        $order = Order::factory()->create();

        $this->assertNotNull($order->order_number);
        $this->assertStringStartsWith('ORD-', $order->order_number);
    }

    public function test_user_can_view_own_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson("/api/orders/{$order->order_number}");

        $response->assertStatus(200);
        $response->assertJson(['id' => $order->id]);
    }
}

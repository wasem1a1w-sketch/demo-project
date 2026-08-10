<?php

namespace Tests\Feature\Kafka;

use App\Enums\OrderStatus;
use App\Kafka\KafkaTopics;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Junges\Kafka\Facades\Kafka;
use Tests\Support\InteractsWithKafka;
use Tests\TestCase;

class OrderEventsKafkaTest extends TestCase
{
    use InteractsWithKafka;
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::factory()->create(['is_active' => true]);
    }

    public function test_placing_order_publishes_order_placed_event_keyed_by_order_id(): void
    {
        $this->fakeKafka();
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 50.00,
            'stock' => 10,
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        $order = app(OrderService::class)->placeOrder([
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
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
        ], null, $user);

        $this->dispatchOutbox();

        Kafka::assertPublishedOn(KafkaTopics::ORDER_EVENTS, callback: function ($message) use ($order, $user): bool {
            $body = $message->getBody();

            return ($body['event_type'] ?? null) === 'order.placed'
                && $message->getKey() === (string) $order->id
                && ($body['data']['order_id'] ?? null) === $order->id
                && ($body['data']['user_id'] ?? null) === $user->id;
        });
    }

    public function test_status_transition_publishes_status_changed_event(): void
    {
        $this->fakeKafka();
        $admin = User::factory()->create();
        $order = Order::factory()->create();

        $order->transitionStatus(OrderStatus::Processing, $admin->id);

        $this->dispatchOutbox();

        Kafka::assertPublishedOn(KafkaTopics::ORDER_EVENTS, callback: function ($message) use ($order, $admin): bool {
            $body = $message->getBody();
            $data = $body['data'] ?? [];

            return ($body['event_type'] ?? null) === 'order.status_changed'
                && ($data['order_id'] ?? null) === $order->id
                && ($data['old_status'] ?? null) === 'pending'
                && ($data['new_status'] ?? null) === 'processing'
                && ($data['actor_user_id'] ?? null) === $admin->id;
        });
    }

    public function test_analytics_consumer_writes_order_activity_logs(): void
    {
        $this->fakeKafka();
        $admin = User::factory()->create();
        $order = Order::factory()->create();

        $order->transitionStatus(OrderStatus::Processing, $admin->id);

        $this->drainOrderEvents();

        $this->assertDatabaseHas('user_activity_logs', [
            'type' => 'order_status_changed',
            'user_id' => $admin->id,
            'description' => "Order #{$order->order_number} status changed: pending -> processing",
        ]);
    }

    public function test_notifications_consumer_sends_customer_notification_and_admin_alert(): void
    {
        Notification::fake();
        $this->fakeKafka();
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $order->transitionStatus(OrderStatus::Processing, $admin->id);

        $this->drainOrderEvents();

        Notification::assertSentTo($user, OrderStatusChanged::class);
        $this->assertDatabaseHas('admin_notifications', ['type' => 'order_status_changed']);
    }

    public function test_order_placement_creates_low_stock_admin_notification(): void
    {
        $this->fakeKafka();
        $product = Product::factory()->create([
            'name' => 'Cheap Widget',
            'price' => 10.00,
            'stock' => 3,
            'is_active' => true,
            'category_id' => $this->category->id,
        ]);

        app(OrderService::class)->placeOrder([
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
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
        ], null, null);

        $this->drainOrderEvents();

        $this->assertDatabaseHas('admin_notifications', [
            'type' => 'low_stock',
        ]);
    }
}

<?php

namespace Tests\Feature\Kafka;

use App\Kafka\KafkaEventPublisher;
use App\Kafka\KafkaTopics;
use App\Kafka\Outbox;
use App\Models\Category;
use App\Models\OutboxMessage;
use App\Models\Product;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Junges\Kafka\Facades\Kafka;
use Tests\Support\InteractsWithKafka;
use Tests\TestCase;

class OutboxTest extends TestCase
{
    use InteractsWithKafka;
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::factory()->create(['is_active' => true]);
    }

    public function test_place_order_records_outbox_message_atomically_and_publishes_on_dispatch(): void
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

        $this->assertDatabaseHas('outbox_messages', [
            'topic' => KafkaTopics::ORDER_EVENTS,
            'status' => OutboxMessage::STATUS_PENDING,
        ]);

        $row = OutboxMessage::where('topic', KafkaTopics::ORDER_EVENTS)->firstOrFail();
        $this->assertEquals('order.placed', $row->payload['event_type']);
        $this->assertEquals($order->id, $row->payload['data']['order_id']);

        Kafka::assertNothingPublished();

        $this->dispatchOutbox();

        $this->assertDatabaseHas('outbox_messages', ['id' => $row->id, 'status' => OutboxMessage::STATUS_SENT]);
        $this->assertNotNull($row->fresh()->sent_at);

        Kafka::assertPublishedOn(KafkaTopics::ORDER_EVENTS, callback: fn ($message): bool => $message->getBody()['event_type'] === 'order.placed');
    }

    public function test_dispatch_does_not_resend_sent_messages(): void
    {
        $this->fakeKafka();

        UserActivityLog::record(5, 'test_event', 'Hello');

        $this->dispatchOutbox();

        $this->assertDatabaseHas('outbox_messages', ['status' => OutboxMessage::STATUS_SENT]);

        $this->dispatchOutbox();

        Kafka::assertPublishedOnTimes(KafkaTopics::ACTIVITY_LOGS, 1);
    }

    public function test_failed_dispatch_retries_with_backoff_then_succeeds(): void
    {
        $this->mock(KafkaEventPublisher::class, function ($mock): void {
            $mock->shouldReceive('publish')->andThrow(new \RuntimeException('broker down'));
        });

        app(Outbox::class)->record(KafkaTopics::ACTIVITY_LOGS, 'activity_log.recorded', ['type' => 'test']);

        app(Outbox::class)->dispatch();

        $message = OutboxMessage::firstOrFail();
        $this->assertEquals(OutboxMessage::STATUS_PENDING, $message->status);
        $this->assertEquals(1, $message->attempts);
        $this->assertNotNull($message->available_at);
        $this->assertTrue($message->available_at->isFuture());

        OutboxMessage::query()->update(['available_at' => now()->subMinute()]);

        $this->mock(KafkaEventPublisher::class, function ($mock): void {
            $mock->shouldReceive('publish')->andReturnNull();
        });

        app(Outbox::class)->dispatch();

        $message->refresh();
        $this->assertEquals(OutboxMessage::STATUS_SENT, $message->status);
        $this->assertEquals(1, $message->attempts);
        $this->assertNotNull($message->sent_at);
    }

    public function test_failure_is_capped_at_max_attempts_and_left_failed(): void
    {
        $this->mock(KafkaEventPublisher::class, function ($mock): void {
            $mock->shouldReceive('publish')->andThrow(new \RuntimeException('broker down'));
        });

        app(Outbox::class)->record(KafkaTopics::ACTIVITY_LOGS, 'activity_log.recorded', ['type' => 'test']);

        $outbox = app(Outbox::class);

        foreach (range(1, 5) as $_) {
            $outbox->dispatch();
            OutboxMessage::query()->update(['available_at' => now()->subMinute()]);
        }

        $message = OutboxMessage::firstOrFail();
        $this->assertEquals(OutboxMessage::STATUS_FAILED, $message->status);
        $this->assertEquals(5, $message->attempts);

        OutboxMessage::query()->update(['available_at' => now()->subMinute()]);

        $outbox->dispatch();

        $message->refresh();
        $this->assertEquals(OutboxMessage::STATUS_FAILED, $message->status);
        $this->assertEquals(5, $message->attempts);
    }
}

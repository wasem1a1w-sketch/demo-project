<?php

namespace Tests\Feature\Kafka;

use App\Enums\PaymentStatus;
use App\Kafka\Consumers\PaymentEventProcessor;
use App\Kafka\EventEnvelope;
use App\Kafka\KafkaTopics;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProcessedPaymentEvent;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\ConsumedMessage;
use Tests\Support\InteractsWithKafka;
use Tests\Support\TestKafkaConsumer;
use Tests\TestCase;

class PaymentEventsKafkaTest extends TestCase
{
    use InteractsWithKafka;
    use RefreshDatabase;

    public function test_webhook_publishes_payment_confirmed_event_and_processor_marks_paid(): void
    {
        $this->fakeKafka();
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'payment_status' => 'pending']);
        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_session_id' => 'cs_test_full_123',
            'status' => PaymentStatus::Pending,
        ]);

        $this->postJson('/api/payments/webhook', [
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test_full_123']],
        ], ['Stripe-Signature' => 'valid_test_signature'])->assertStatus(200);

        $this->dispatchOutbox();

        Kafka::assertPublishedOn(KafkaTopics::PAYMENT_EVENTS, callback: fn ($message): bool => $message->getBody()['event_type'] === 'payment.confirmed');

        $this->drainPaymentEvents();

        $this->assertEquals('paid', $payment->fresh()->status->value);
        $this->assertEquals('paid', $order->fresh()->payment_status->value);
    }

    public function test_processor_is_idempotent_for_duplicate_events(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'payment_status' => 'pending']);
        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_session_id' => 'cs_test_dup_123',
            'status' => PaymentStatus::Pending,
        ]);

        $envelope = EventEnvelope::make('payment.confirmed', ['session_id' => 'cs_test_dup_123'], 'cs_test_dup_123');
        $message = $this->paymentMessage($envelope);
        $processor = app(PaymentEventProcessor::class);

        $processor($message, new TestKafkaConsumer);
        $processor($message, new TestKafkaConsumer);

        $this->assertEquals('paid', $payment->fresh()->status->value);
        $this->assertEquals(1, ProcessedPaymentEvent::count());
    }

    public function test_processor_retries_transient_failures_then_sends_to_dlq(): void
    {
        $this->fakeKafka();
        $this->mock(PaymentService::class, function ($mock): void {
            $mock->shouldReceive('handleCheckoutComplete')
                ->andThrow(new \RuntimeException('provider timeout'));
        });

        $envelope = EventEnvelope::make('payment.confirmed', ['session_id' => 'cs_test_retry_123'], 'cs_test_retry_123');
        $processor = app(PaymentEventProcessor::class);

        $attempt = function (int $attempts) use ($envelope, $processor): void {
            $data = $envelope->data;
            if ($attempts > 0) {
                $data['attempts'] = $attempts;
            }
            $retryEnvelope = new EventEnvelope(
                eventType: $envelope->eventType,
                data: $data,
                key: $envelope->key,
                eventId: $envelope->eventId.'-'.$attempts,
                occurredAt: $envelope->occurredAt,
            );
            $processor($this->paymentMessage($retryEnvelope), new TestKafkaConsumer);
        };

        // Attempt 0 -> goes to retry topic
        $attempt(0);
        Kafka::assertPublishedOn(KafkaTopics::PAYMENT_EVENTS_RETRY, callback: fn ($m): bool => ($m->getBody()['data']['attempts'] ?? null) === 1);

        // Attempt 1 -> still failing, back to retry
        $attempt(1);
        Kafka::assertPublishedOn(KafkaTopics::PAYMENT_EVENTS_RETRY, callback: fn ($m): bool => ($m->getBody()['data']['attempts'] ?? null) === 2);

        // Attempt 2 (max) -> dead letter queue
        $attempt(2);
        Kafka::assertPublishedOn(KafkaTopics::PAYMENT_EVENTS_DLQ, callback: fn ($m): bool => ($m->getBody()['data']['attempts'] ?? null) === 2);

        // Every failed attempt released its idempotency claim.
        $this->assertEquals(0, ProcessedPaymentEvent::count());
    }

    public function test_payment_failed_webhook_cancels_order_via_consumer(): void
    {
        $this->fakeKafka();
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'payment_status' => 'pending']);
        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_session_id' => 'cs_test_fail_123',
            'status' => PaymentStatus::Pending,
        ]);

        $this->postJson('/api/payments/webhook', [
            'type' => 'payment_intent.payment_failed',
            'data' => ['object' => ['id' => 'cs_test_fail_123']],
        ], ['Stripe-Signature' => 'valid_test_signature'])->assertStatus(200);

        $this->drainPaymentEvents();

        $this->assertEquals('failed', $payment->fresh()->status->value);
        $this->assertEquals('failed', $order->fresh()->payment_status->value);
    }

    private function paymentMessage(EventEnvelope $envelope): ConsumedMessage
    {
        return new ConsumedMessage(
            topicName: KafkaTopics::PAYMENT_EVENTS,
            partition: 0,
            headers: [],
            body: $envelope->toArray(),
            key: $envelope->key,
            offset: 0,
            timestamp: time(),
        );
    }
}

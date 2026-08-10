<?php

namespace Tests\Support;

use App\Kafka\Consumers\ActivityLogHandler;
use App\Kafka\Consumers\OrderAnalyticsHandler;
use App\Kafka\Consumers\OrderNotificationsHandler;
use App\Kafka\Consumers\PaymentEventProcessor;
use App\Kafka\KafkaTopics;
use App\Kafka\Outbox;
use Junges\Kafka\Contracts\ProducerMessage;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\ConsumedMessage;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Helpers to simulate the Kafka consumer fleet in feature tests.
 *
 * Producers record into the transactional outbox, so tests first run
 * dispatchOutbox() to deliver pending rows to the faked broker, then
 * drainKafkaMessages() routes every captured message through its real
 * consumer handler, mirroring the async worker topology in production.
 */
trait InteractsWithKafka
{
    protected function fakeKafka(): void
    {
        Kafka::fake();
    }

    /** Deliver pending outbox rows to the (faked) broker. */
    protected function dispatchOutbox(): void
    {
        app(Outbox::class)->dispatch();
    }

    protected function drainKafkaMessages(): void
    {
        $this->dispatchOutbox();
        $this->drainActivityLogs();
        $this->drainOrderEvents();
        $this->drainPaymentEvents();
    }

    protected function drainActivityLogs(): void
    {
        $this->dispatchOutbox();

        $this->drainTopic(KafkaTopics::ACTIVITY_LOGS, function (ProducerMessage $message): void {
            $this->invokeHandler(ActivityLogHandler::class, $message);
        });
    }

    protected function drainOrderEvents(): void
    {
        $this->dispatchOutbox();

        $this->drainTopic(KafkaTopics::ORDER_EVENTS, function (ProducerMessage $message): void {
            // order-events is consumed by two independent consumer groups.
            $this->invokeHandler(OrderNotificationsHandler::class, $message);
            $this->invokeHandler(OrderAnalyticsHandler::class, $message);
        });
    }

    protected function drainPaymentEvents(): void
    {
        $this->dispatchOutbox();

        $this->drainTopic(KafkaTopics::PAYMENT_EVENTS, function (ProducerMessage $message): void {
            $this->invokeHandler(PaymentEventProcessor::class, $message);
        });

        $this->drainTopic(KafkaTopics::PAYMENT_EVENTS_RETRY, function (ProducerMessage $message): void {
            $this->invokeHandler(PaymentEventProcessor::class, $message);
        });
    }

    /** Capture a topic's messages and run them through a handler (no-op if empty). */
    private function drainTopic(string $topic, callable $callback): void
    {
        try {
            Kafka::assertPublishedOn($topic, callback: function (ProducerMessage $message) use ($callback): bool {
                $callback($message);

                return true;
            });
        } catch (AssertionFailedError) {
            // Nothing was published to this topic during the request.
        }
    }

    private function invokeHandler(string $handlerClass, ProducerMessage $message): void
    {
        $consumed = new ConsumedMessage(
            topicName: $message->getTopicName(),
            partition: 0,
            headers: $message->getHeaders() ?? [],
            body: $message->getBody() ?? [],
            key: $message->getKey(),
            offset: 0,
            timestamp: time(),
        );

        app($handlerClass)($consumed, new TestKafkaConsumer);
    }
}

<?php

namespace Tests\Feature\Kafka;

use App\Kafka\KafkaTopics;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Junges\Kafka\Facades\Kafka;
use Tests\Support\InteractsWithKafka;
use Tests\TestCase;

class ActivityLogKafkaTest extends TestCase
{
    use InteractsWithKafka;
    use RefreshDatabase;

    public function test_record_publishes_activity_log_envelope(): void
    {
        $this->fakeKafka();

        UserActivityLog::record(5, 'test_event', 'Hello world', ['foo' => 'bar']);

        $this->dispatchOutbox();

        Kafka::assertPublishedOn(KafkaTopics::ACTIVITY_LOGS, callback: function ($message): bool {
            $body = $message->getBody();

            return ($body['event_type'] ?? null) === 'activity_log.recorded'
                && ($body['key'] ?? null) === '5'
                && ($body['data']['user_id'] ?? null) === 5
                && ($body['data']['type'] ?? null) === 'test_event'
                && ($body['data']['description'] ?? null) === 'Hello world'
                && ($body['data']['data'] ?? null) === ['foo' => 'bar'];
        });
    }

    public function test_record_uses_order_key_for_partitioning(): void
    {
        $this->fakeKafka();

        UserActivityLog::record(42, 'test_event', 'Hello');

        $this->dispatchOutbox();

        Kafka::assertPublishedOn(KafkaTopics::ACTIVITY_LOGS, callback: fn ($message): bool => $message->getKey() === '42');
    }

    public function test_consumer_persists_published_activity_logs(): void
    {
        $this->fakeKafka();
        $user = User::factory()->create();

        UserActivityLog::record($user->id, 'test_event', 'Hello world');

        $this->drainActivityLogs();

        $this->assertDatabaseHas('user_activity_logs', [
            'user_id' => $user->id,
            'type' => 'test_event',
            'description' => 'Hello world',
        ]);
    }
}

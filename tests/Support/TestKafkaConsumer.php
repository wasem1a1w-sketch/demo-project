<?php

namespace Tests\Support;

use Junges\Kafka\Contracts\MessageConsumer;

class TestKafkaConsumer implements MessageConsumer
{
    public int $committedMessages = 0;

    public function consume(): void {}

    public function stopConsuming(): void {}

    public function cancelStopConsume(): void {}

    public function consumedMessagesCount(): int
    {
        return 0;
    }

    public function commit(mixed $messageOrOffsets = null): void
    {
        $this->committedMessages++;
    }

    public function commitAsync(mixed $message_or_offsets = null): void {}

    public function getAssignedPartitions(): array
    {
        return [];
    }
}

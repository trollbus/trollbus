<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\MessageId;

use Trollbus\MessageBus\MessageId\MessageIdGenerator;

final class SequenceMessageIdGenerator implements MessageIdGenerator
{
    private int $index = 0;

    public function generate(): string
    {
        ++$this->index;

        return (string) $this->index;
    }

    public function reset(): void
    {
        $this->index = 0;
    }
}

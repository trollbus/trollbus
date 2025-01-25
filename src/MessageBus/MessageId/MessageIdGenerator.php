<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\MessageId;

interface MessageIdGenerator
{
    /**
     * @return non-empty-string
     */
    public function generate(): string;
}

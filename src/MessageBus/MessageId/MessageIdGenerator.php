<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\MessageId;

interface MessageIdGenerator
{
    /**
     * @return non-empty-string
     */
    public function generate(): string;
}

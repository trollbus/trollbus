<?php

declare(strict_types=1);

namespace Trollbus\Async;

use Trollbus\MessageBus\ContextAttribute;

final class Queue implements ContextAttribute
{
    /**
     * @param non-empty-string $queue
     */
    public function __construct(
        public readonly string $queue,
    ) {}
}

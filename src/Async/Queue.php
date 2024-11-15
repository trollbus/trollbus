<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\Async;

use Kenny1911\SisyphBus\MessageBus\ContextAttribute;

final class Queue implements ContextAttribute
{
    /** @var non-empty-string */
    public readonly string $queue;

    /**
     * @param non-empty-string $queue
     */
    public function __construct(string $queue)
    {
        $this->queue = $queue;
    }
}

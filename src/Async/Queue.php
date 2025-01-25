<?php

declare(strict_types=1);

namespace Trollbus\Async;

use Trollbus\MessageBus\ContextAttribute;

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

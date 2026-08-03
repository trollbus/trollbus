<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Async\MessageType;

use Trollbus\MessageBus\Stamp;

final class MessageType implements Stamp
{
    /**
     * @param non-empty-string $type
     */
    public function __construct(
        public readonly string $type,
    ) {}
}

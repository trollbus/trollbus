<?php

declare(strict_types=1);

namespace Trollbus\Async;

use Trollbus\MessageBus\Stamp;

final class Exchange implements Stamp
{
    /**
     * @param non-empty-string $exchange
     */
    public function __construct(
        public readonly string $exchange,
    ) {}
}

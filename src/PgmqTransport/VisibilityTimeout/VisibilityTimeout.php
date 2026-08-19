<?php

declare(strict_types=1);

namespace Trollbus\PgmqTransport\VisibilityTimeout;

use Trollbus\MessageBus\Stamp;

final class VisibilityTimeout implements Stamp
{
    /**
     * @param positive-int $seconds
     */
    public function __construct(
        public readonly int $seconds = 30,
    ) {}
}

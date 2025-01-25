<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\MessageId;

use Trollbus\MessageBus\Stamp;

final class CorrelationId implements Stamp
{
    /**
     * @param non-empty-string $correlationId
     */
    public function __construct(
        public readonly string $correlationId,
    ) {}
}

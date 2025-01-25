<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\MessageId;

use Trollbus\MessageBus\Stamp;

final class CorrelationId implements Stamp
{
    /** @var non-empty-string */
    public readonly string $correlationId;

    /**
     * @param non-empty-string $correlationId
     */
    public function __construct(string $correlationId)
    {
        $this->correlationId = $correlationId;
    }
}

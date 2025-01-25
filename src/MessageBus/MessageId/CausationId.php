<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\MessageId;

use Trollbus\MessageBus\Stamp;

final class CausationId implements Stamp
{
    /**
     * @param non-empty-string|null $causationId
     */
    public function __construct(
        public readonly ?string $causationId,
    ) {}
}

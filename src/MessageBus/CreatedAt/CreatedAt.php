<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\CreatedAt;

use Trollbus\MessageBus\Stamp;

final class CreatedAt implements Stamp
{
    public function __construct(
        public readonly \DateTimeImmutable $createdAt,
    ) {}
}

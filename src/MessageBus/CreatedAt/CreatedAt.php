<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\CreatedAt;

use Trollbus\MessageBus\Stamp;

final class CreatedAt implements Stamp
{
    public readonly \DateTimeImmutable $createdAt;

    public function __construct(\DateTimeImmutable $createdAt)
    {
        $this->createdAt = $createdAt;
    }
}

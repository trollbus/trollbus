<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\CreatedAt;

use Kenny1911\SisyphBus\MessageBus\Stamp;

final class CreatedAt implements Stamp
{
    public readonly \DateTimeImmutable $createdAt;

    public function __construct(\DateTimeImmutable $createdAt)
    {
        $this->createdAt = $createdAt;
    }
}

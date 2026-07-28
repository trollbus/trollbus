<?php

declare(strict_types=1);

namespace App\EntityHandler\Attribute\Event;

use Symfony\Component\Uid\Uuid;
use Trollbus\Message\Event;

final readonly class PostUpdated implements Event
{
    public function __construct(
        public Uuid $id,
        public \DateTimeImmutable $timestamp,
    ) {}
}

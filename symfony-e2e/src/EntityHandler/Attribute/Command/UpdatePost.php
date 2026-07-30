<?php

declare(strict_types=1);

namespace App\EntityHandler\Attribute\Command;

use Symfony\Component\Uid\Uuid;
use Trollbus\Message\Message;

/**
 * @implements Message<void>
 */
final readonly class UpdatePost implements Message
{
    public function __construct(
        public Uuid $id,
        public string $title,
        public string $body,
        public \DateTimeImmutable $timestamp,
    ) {}
}

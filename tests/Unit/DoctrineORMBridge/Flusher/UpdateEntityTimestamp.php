<?php

declare(strict_types=1);

namespace Trollbus\Tests\Unit\DoctrineORMBridge\Flusher;

use Trollbus\Message\Message;

/**
 * @implements Message<void>
 */
final class UpdateEntityTimestamp implements Message
{
    public function __construct(
        public readonly string $id,
        public readonly \DateTimeImmutable $timestamp,
    ) {}
}

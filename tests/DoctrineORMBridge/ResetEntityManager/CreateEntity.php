<?php

declare(strict_types=1);

namespace Trollbus\Tests\DoctrineORMBridge\ResetEntityManager;

use Trollbus\Message\Message;

/**
 * @implements Message<void>
 */
final class CreateEntity implements Message
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
    ) {}
}

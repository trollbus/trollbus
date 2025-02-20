<?php

declare(strict_types=1);

namespace Trollbus\Tests\DoctrineORMBridge\EntityHandler;

use Trollbus\Message\Message;

/**
 * @implements Message<void>
 */
final class EditEntity implements Message
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
    ) {}
}

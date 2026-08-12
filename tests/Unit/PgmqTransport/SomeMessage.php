<?php

declare(strict_types=1);

namespace Trollbus\Tests\Unit\PgmqTransport;

use Trollbus\Message\Message;

/**
 * @implements Message<void>
 */
final class SomeMessage implements Message
{
    public function __construct(
        public readonly string $foo,
    ) {}
}

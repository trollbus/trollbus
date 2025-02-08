<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\MessageBusTestCases\SimpleMessage;

use Trollbus\Message\Message;

/**
 * @implements Message<SimpleMessageResult>
 */
final class SimpleMessage implements Message
{
    public function __construct(
        public readonly int $foo,
        public readonly int $bar,
    ) {}
}

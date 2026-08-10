<?php

declare(strict_types=1);

namespace Trollbus\Tests\Unit\MessageBus\MessageBusTestCases\SimpleMessage;

final class SimpleMessageResult
{
    public function __construct(
        public readonly int $foo,
        public readonly int $bar,
    ) {}
}

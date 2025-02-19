<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\MessageBusTestCases\SimpleMessage;

use Trollbus\MessageBus\Stamp;

final class SimpleMessageStamp implements Stamp
{
    public function __construct(
        public readonly string $value,
    ) {}
}

<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\MessageBusTestCases\SimpleMessage;

use Trollbus\Tests\MessageBus\MessageBusTestCases\SimpleMessage\SimpleMessage;
use Trollbus\Tests\MessageBus\MessageBusTestCases\SimpleMessage\SimpleMessageResult;

final class SimpleMessageManager
{
    public function handleMessage(SimpleMessage $message): SimpleMessageResult
    {
        return new SimpleMessageResult(
            foo: $message->foo,
            bar: $message->bar,
        );
    }
}

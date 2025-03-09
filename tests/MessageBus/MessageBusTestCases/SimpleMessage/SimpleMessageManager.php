<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\MessageBusTestCases\SimpleMessage;

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

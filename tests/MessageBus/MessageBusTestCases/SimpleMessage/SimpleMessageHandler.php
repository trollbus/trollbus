<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\MessageBusTestCases\SimpleMessage;

use Trollbus\MessageBus\Handler;
use Trollbus\MessageBus\MessageContext;

/**
 * @implements Handler<SimpleMessageResult, SimpleMessage>
 */
final class SimpleMessageHandler implements Handler
{
    public function id(): string
    {
        return 'simple-query';
    }

    public function handle(MessageContext $messageContext): mixed
    {
        /** @var SimpleMessage $message */
        $message = $messageContext->getMessage();

        return new SimpleMessageResult(
            foo: $message->foo,
            bar: $message->bar,
        );
    }
}

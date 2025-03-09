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
    #[\Override]
    public function id(): string
    {
        return 'simple-query';
    }

    #[\Override]
    public function handle(MessageContext $messageContext): mixed
    {
        /** @var SimpleMessage $message */
        $message = $messageContext->getMessage();

        return (new SimpleMessageManager())->handleMessage($message);
    }
}

<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\MessageBusTestCases\NestedDispatch;

use Trollbus\MessageBus\Handler;
use Trollbus\MessageBus\MessageContext;

/**
 * @implements Handler<null, NestedDispatchLevel1>
 */
final class NestedDispatchLevel1Handler implements Handler
{
    public function id(): string
    {
        return 'nested-dispatch-level-1';
    }

    public function handle(MessageContext $messageContext): mixed
    {
        $messageContext->dispatch(new NestedDispatchLevel2());

        return null;
    }
}

<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\MessageBusTestCases\NestedDispatch;

use Trollbus\MessageBus\Handler;
use Trollbus\MessageBus\MessageContext;

/**
 * @implements Handler<null, NestedDispatchLevel2>
 */
final class NestedDispatchLevel2Handler implements Handler
{
    public function id(): string
    {
        return 'nested-dispatch-level-2';
    }

    public function handle(MessageContext $messageContext): mixed
    {
        $messageContext->dispatch(new NestedDispatchLevel3());

        return null;
    }
}

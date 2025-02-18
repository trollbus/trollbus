<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\MessageBusTestCases\NestedDispatch;

use Trollbus\MessageBus\Handler;
use Trollbus\MessageBus\MessageContext;

/**
 * @implements Handler<null, NestedDispatchLevel3>
 */
final class NestedDispatchLevel3Handler implements Handler
{
    #[\Override]
    public function id(): string
    {
        return 'nested-dispatch-level-3';
    }

    #[\Override]
    public function handle(MessageContext $messageContext): mixed
    {
        return null;
    }
}

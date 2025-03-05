<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\MessageBusTestCases\HandlerMiddleware;

use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;

final class HandlerMiddleware implements Middleware
{
    #[\Override]
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        $messageContext->addStamps(new HandlerMiddlewareStamp());

        return $pipeline->continue();
    }
}

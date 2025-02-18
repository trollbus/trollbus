<?php

declare(strict_types=1);

namespace Trollbus\Async;

use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;

final class AddExchangeMiddleware implements Middleware
{
    public function __construct(
        private readonly ExchangeResolver $exchangeResolver,
    ) {}

    #[\Override]
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if (!$messageContext->hasStamp(Exchange::class)) {
            $messageContext->addStamps(new Exchange($this->exchangeResolver->resolve($messageContext->getMessageClass())));
        }

        return $pipeline->continue();
    }
}

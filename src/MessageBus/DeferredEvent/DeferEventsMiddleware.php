<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\DeferredEvent;

use Trollbus\Message\Event;
use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;

/**
 * Global message bus middleware for defer handling events messages.
 */
final class DeferEventsMiddleware implements Middleware
{
    public function __construct(
        private readonly DeferredEventsStorage $deferredEventsStorage,
    ) {}

    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        $parentMessageContext = $messageContext->parent;

        if (null === $parentMessageContext) {
            return $pipeline->continue();
        }

        if (false === $parentMessageContext->hasAttribute(DeferEvents::class)) {
            return $pipeline->continue();
        }

        if (false === $messageContext->getMessage() instanceof Event) {
            return $pipeline->continue();
        }

        $this->deferredEventsStorage->addPipeline($parentMessageContext, $pipeline);

        return null;
    }
}

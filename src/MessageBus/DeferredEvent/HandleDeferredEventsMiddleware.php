<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\DeferredEvent;

use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;

/**
 * Message handler middleware for defer handling events messages.
 */
final class HandleDeferredEventsMiddleware implements Middleware
{
    public function __construct(
        private readonly DeferredEventsStorage $deferredEventsStorage,
    ) {}

    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        $messageContext->addAttributes(new DeferEvents());
        $result = $pipeline->continue();

        foreach ($this->deferredEventsStorage->pullChildEventsPipelines($messageContext) as $childEventsPipeline) {
            $childEventsPipeline->continue();
        }

        return $result;
    }
}

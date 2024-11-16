<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\Async\Outbox;

use Kenny1911\SisyphBus\MessageBus\MessageContext;
use Kenny1911\SisyphBus\MessageBus\Middleware\Middleware;
use Kenny1911\SisyphBus\MessageBus\Middleware\Pipeline;

final class TryPublishViaOutboxMiddleware implements Middleware
{
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        $outbox = $messageContext->getAttribute(Outbox::class);

        if (null === $outbox) {
            return $pipeline->continue();
        }

        $outbox->addEnvelope($messageContext->envelop);

        return null;
    }
}

<?php

declare(strict_types=1);

namespace Trollbus\Async\Outbox;

use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;

final class TryPublishViaOutboxMiddleware implements Middleware
{
    #[\Override]
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        $outbox = $messageContext->getAttribute(Outbox::class);

        if (null === $outbox) {
            return $pipeline->continue();
        }

        $outbox->addEnvelope($messageContext->getEnvelop());

        return null;
    }
}

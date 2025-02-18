<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\MessageId;

use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;

final class CausationIdMiddleware implements Middleware
{
    /**
     * @throws MessageIdNotSet
     */
    #[\Override]
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if ($messageContext->hasStamp(CausationId::class)) {
            return $pipeline->continue();
        }

        $messageContext->addStamps(new CausationId($messageContext->parent?->getMessageId() ?? null));

        return $pipeline->continue();
    }
}

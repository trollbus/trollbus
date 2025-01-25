<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\MessageId;

use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\MessageId\Exception\MessageIdNotSet;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;

final class CorrelationIdMiddleware implements Middleware
{
    /**
     * @throws MessageIdNotSet
     */
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if ($messageContext->hasStamp(CorrelationId::class)) {
            return $pipeline->continue();
        }

        $messageContext->addStamps(
            new CorrelationId(
                $messageContext->parent?->getStamp(CorrelationId::class)->correlationId ?? $messageContext->getMessageId(),
            ),
        );

        return $pipeline->continue();
    }
}

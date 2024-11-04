<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\MessageId;

use Kenny1911\SisyphBus\MessageBus\MessageContext;
use Kenny1911\SisyphBus\MessageBus\MessageId\Exception\MessageIdNotSet;
use Kenny1911\SisyphBus\MessageBus\Middleware\Middleware;
use Kenny1911\SisyphBus\MessageBus\Middleware\Pipeline;

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

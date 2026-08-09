<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Async\MessageType;

use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;

final class AddMessageTypeMiddleware implements Middleware
{
    public function __construct(
        private readonly MessageTypeResolver $resolver,
    ) {}

    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if ($messageContext->hasStamp(MessageType::class)) {
            return $pipeline->continue();
        }

        $messageContext->addStamps(new MessageType(type: $this->resolver->resolveType($messageContext->getMessageClass())));

        return $pipeline->continue();
    }
}

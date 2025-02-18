<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\MessageId;

use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;

final class MessageIdMiddleware implements Middleware
{
    public function __construct(
        private readonly MessageIdGenerator $generator,
    ) {}

    #[\Override]
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if (!$messageContext->hasStamp(MessageId::class)) {
            $messageContext->addStamps(new MessageId($this->generator->generate()));
        }

        return $pipeline->continue();
    }
}

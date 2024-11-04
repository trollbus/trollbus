<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\MessageId;

use Kenny1911\SisyphBus\MessageBus\MessageContext;
use Kenny1911\SisyphBus\MessageBus\Middleware\Middleware;
use Kenny1911\SisyphBus\MessageBus\Middleware\Pipeline;

final class MessageIdMiddleware implements Middleware
{
    private readonly MessageIdGenerator $generator;

    public function __construct(MessageIdGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if (!$messageContext->hasStamp(MessageId::class)) {
            $messageContext->addStamps(new MessageId($this->generator->generate()));
        }

        return $pipeline->continue();
    }
}

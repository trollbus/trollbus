<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\MessageId;

use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;

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

<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\MessageContextStack;

use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;

/**
 * Special middleware for testing, that collect all message contexts.
 */
final class MessageContextStackMiddleware implements Middleware
{
    public function __construct(
        private readonly MessageContextStack $messageContextStack,
    ) {}

    #[\Override]
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        $this->messageContextStack->push($messageContext);

        return $pipeline->continue();
    }
}

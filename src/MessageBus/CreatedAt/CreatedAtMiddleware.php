<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\CreatedAt;

use Psr\Clock\ClockInterface;
use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;

final class CreatedAtMiddleware implements Middleware
{
    public function __construct(
        private readonly ?ClockInterface $clock = null,
    ) {}

    #[\Override]
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if (!$messageContext->hasStamp(CreatedAt::class)) {
            $messageContext->addStamps(new CreatedAt($this->clock?->now() ?? new \DateTimeImmutable()));
        }

        return $pipeline->continue();
    }
}

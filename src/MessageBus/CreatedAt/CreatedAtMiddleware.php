<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\CreatedAt;

use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;
use Psr\Clock\ClockInterface;

final class CreatedAtMiddleware implements Middleware
{
    private readonly ?ClockInterface $clock;

    public function __construct(?ClockInterface $clock = null)
    {
        $this->clock = $clock;
    }

    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if (!$messageContext->hasStamp(CreatedAt::class)) {
            $messageContext->addStamps(new CreatedAt($this->clock?->now() ?? new \DateTimeImmutable()));
        }

        return $pipeline->continue();
    }
}

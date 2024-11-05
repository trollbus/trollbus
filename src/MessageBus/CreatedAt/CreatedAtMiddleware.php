<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\CreatedAt;

use Kenny1911\SisyphBus\MessageBus\MessageContext;
use Kenny1911\SisyphBus\MessageBus\Middleware\Middleware;
use Kenny1911\SisyphBus\MessageBus\Middleware\Pipeline;
use Psr\Clock\ClockInterface;

final class CreatedAtMiddleware implements Middleware
{
    private readonly ?ClockInterface $clock;

    public function __construct(?ClockInterface $clock)
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

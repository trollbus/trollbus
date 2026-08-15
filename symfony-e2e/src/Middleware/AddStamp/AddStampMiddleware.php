<?php

declare(strict_types=1);

namespace App\Middleware\AddStamp;

use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;
use Trollbus\MessageBus\Stamp;

final readonly class AddStampMiddleware implements Middleware
{
    public function __construct(
        private Stamp $stamp,
    ) {}

    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        $messageContext->addStamps($this->stamp);

        return $pipeline->continue();
    }
}

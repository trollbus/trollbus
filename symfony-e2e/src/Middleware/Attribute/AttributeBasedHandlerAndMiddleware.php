<?php

declare(strict_types=1);

namespace App\Middleware\Attribute;

use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\Pipeline;
use Trollbus\TrollbusBundle\Attribute\Handler;
use Trollbus\TrollbusBundle\Attribute\Middleware;
use Trollbus\TrollbusBundle\Attribute\WithMiddleware;

final class AttributeBasedHandlerAndMiddleware
{
    private const string MIDDLEWARE = 'app.test.attribute_middleware';

    private array $callChain = [];

    #[Handler]
    #[WithMiddleware(serviceId: self::MIDDLEWARE)]
    public function someCommand(SomeCommand $command): void
    {
        $this->callChain['handler'] = $command;
    }

    #[Middleware(serviceId: self::MIDDLEWARE)]
    public function middleware(Pipeline $pipeline, MessageContext $context): mixed
    {
        $this->callChain['middleware'] = $context->getMessage();

        return $pipeline->continue();
    }

    #[Middleware(global: true)]
    public function globalMiddleware(Pipeline $pipeline, MessageContext $context): mixed
    {
        $this->callChain['globalMiddleware'] = $context->getMessage();

        return $pipeline->continue();
    }

    public function pullCallChain(): array
    {
        $callChain = $this->callChain;
        $this->callChain = [];

        return $callChain;
    }
}

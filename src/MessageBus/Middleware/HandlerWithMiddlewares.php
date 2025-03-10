<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Middleware;

use Trollbus\Message\Message;
use Trollbus\MessageBus\Handler;
use Trollbus\MessageBus\MessageContext;

/**
 * @template TResult
 * @template TMessage of Message<TResult>
 * @implements Handler<TResult, TMessage>
 */
final class HandlerWithMiddlewares implements Handler
{
    /**
     * @param Handler<TResult, TMessage> $inner
     * @param iterable<Middleware> $middlewares
     */
    public function __construct(
        private readonly Handler $inner,
        private readonly iterable $middlewares,
    ) {}

    #[\Override]
    public function id(): string
    {
        return $this->inner->id();
    }

    #[\Override]
    public function handle(MessageContext $messageContext): mixed
    {
        return Pipeline::handle($messageContext, $this->inner, $this->middlewares);
    }
}

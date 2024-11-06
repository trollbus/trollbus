<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\Middleware;

use Kenny1911\SisyphBus\Message\Message;
use Kenny1911\SisyphBus\MessageBus\Envelop;
use Kenny1911\SisyphBus\MessageBus\Handler;
use Kenny1911\SisyphBus\MessageBus\MessageContext;

/**
 * @template TResult
 * @template TMessage of Message<TResult>
 * @implements Handler<TResult, TMessage>
 */
final class HandlerWithMiddlewares implements Handler
{
    /** @var Handler<TResult, TMessage> */
    private readonly Handler $inner;

    /** @var iterable<Middleware> */
    private iterable $middlewares;

    /**
     * @param Handler<TResult, TMessage> $inner
     * @param iterable<Middleware> $middlewares
     */
    public function __construct(Handler $inner, iterable $middlewares)
    {
        $this->inner = $inner;
        $this->middlewares = $middlewares;
    }

    public function id(): string
    {
        return $this->inner->id();
    }

    public function handle(MessageContext $messageContext): mixed
    {
        return Pipeline::handle($messageContext, $this->inner, $this->middlewares);
    }
}

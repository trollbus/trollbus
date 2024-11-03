<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\Middleware;

use Kenny1911\SisyphBus\Message\Message;
use Kenny1911\SisyphBus\MessageBus\Handler;

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

    public function handle(Message $message): mixed
    {
        return Pipeline::handle($message, $this->inner, $this->middlewares);
    }
}

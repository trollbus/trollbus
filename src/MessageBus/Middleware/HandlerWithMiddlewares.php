<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Middleware;

use Trollbus\Message\Message;
use Trollbus\MessageBus\Envelope;
use Trollbus\MessageBus\Handler;
use Trollbus\MessageBus\MessageContext;

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

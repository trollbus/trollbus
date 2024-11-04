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
 */
final class Pipeline
{
    /** @var MessageContext<TResult, TMessage> */
    private readonly MessageContext $messageContext;

    /** @var Handler<TResult, TMessage> */
    private readonly Handler $handler;

    /** @var list<Middleware> */
    private array $middlewares;

    private bool $handled = false;

    /**
     * @param MessageContext<TResult, TMessage> $messageContext
     * @param Handler<TResult, TMessage> $handler
     * @param list<Middleware> $middlewares
     */
    public function __construct(MessageContext $messageContext, Handler $handler, array $middlewares)
    {
        $this->messageContext = $messageContext;
        $this->handler = $handler;
        $this->middlewares = $middlewares;
    }

    /**
     * @template TTResult
     * @template TTMessage of Message<TTResult>
     * @param MessageContext<TTResult, TTMessage> $messageContext
     * @param Handler<TTResult, TTMessage> $handler
     * @param iterable<Middleware> $middlewares
     * @return (TTResult is void ? null : TTResult)
     */
    public static function handle(MessageContext $messageContext, Handler $handler, iterable $middlewares): mixed
    {
        $middlewares = $middlewares instanceof \Traversable ? iterator_to_array($middlewares, false) : array_values($middlewares);

        return (new self($messageContext, $handler, $middlewares))->continue();
    }

    /**
     * @return (TResult is void ? null : TResult)
     */
    public function continue(): mixed
    {
        if (true === $this->handled) {
            throw new \LogicException('Pipeline already handled.');
        }

        /** @var Middleware|null $middleware */
        $middleware = array_shift($this->middlewares);

        if (null !== $middleware) {
            return $middleware->handle($this->messageContext, $this);
        }

        $this->handled = true;

        return $this->handler->handle($this->messageContext);
    }
}

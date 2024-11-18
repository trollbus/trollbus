<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\Middleware;

use Kenny1911\SisyphBus\Message\Message;
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

    /** @var \Iterator<Middleware> */
    private readonly \Iterator $middlewares;

    private bool $handled = false;

    private bool $started = false;

    /**
     * @param MessageContext<TResult, TMessage> $messageContext
     * @param Handler<TResult, TMessage> $handler
     * @param \Iterator<Middleware> $middlewares
     */
    private function __construct(MessageContext $messageContext, Handler $handler, \Iterator $middlewares)
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
        $middlewares = \is_array($middlewares) ? new \ArrayIterator($middlewares) : new \IteratorIterator($middlewares);

        return (new self($messageContext, $handler, $middlewares))->continue();
    }

    /**
     * @return non-empty-string
     */
    public function id(): string
    {
        return $this->handler->id();
    }

    /**
     * @return (TResult is void ? null : TResult)
     */
    public function continue(): mixed
    {
        if (true === $this->handled) {
            throw new \LogicException('Pipeline already handled.');
        }

        if ($this->started) {
            $this->middlewares->next();
        } else {
            $this->started = true;
        }

        if ($this->middlewares->valid()) {
            return $this->middlewares->current()->handle($this->messageContext, $this);
        }

        $this->handled = true;

        return $this->handler->handle($this->messageContext);
    }
}

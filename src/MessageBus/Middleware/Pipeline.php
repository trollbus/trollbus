<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\Middleware;

use Kenny1911\SisyphBus\Message\Message;
use Kenny1911\SisyphBus\MessageBus\Handler;

/**
 * @template TResult
 * @template TMessage of Message<TResult>
 */
final class Pipeline
{
    /** @var TMessage */
    private readonly Message $message;

    /** @var Handler<TResult, TMessage> */
    private readonly Handler $handler;

    /** @var list<Middleware> */
    private array $middlewares;

    private bool $handled = false;

    /**
     * @param TMessage $message
     * @param Handler<TResult, TMessage> $handler
     * @param list<Middleware> $middlewares
     */
    public function __construct(Message $message, Handler $handler, array $middlewares)
    {
        $this->message = $message;
        $this->handler = $handler;
        $this->middlewares = $middlewares;
    }

    /**
     * @template TTResult
     * @template TTMessage of Message<TTResult>
     * @param TTMessage $message
     * @param Handler<TTResult, TTMessage> $handler
     * @param iterable<Middleware> $middlewares
     * @return (TTResult is void ? null : TTResult)
     */
    public static function handle(Message $message, Handler $handler, iterable $middlewares): mixed
    {
        $middlewares = $middlewares instanceof \Traversable ? iterator_to_array($middlewares, false) : array_values($middlewares);

        return (new self($message, $handler, $middlewares))->continue();
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
            return $middleware->handle($this->message, $this);
        }

        $this->handled = true;

        return $this->handler->handle($this->message);
    }
}

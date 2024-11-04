<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\Middleware;

use Kenny1911\SisyphBus\Message\Message;
use Kenny1911\SisyphBus\MessageBus\Envelop;
use Kenny1911\SisyphBus\MessageBus\Handler;

/**
 * @template TResult
 * @template TMessage of Message<TResult>
 */
final class Pipeline
{
    /** @var Envelop<TResult, TMessage> */
    private readonly Envelop $envelop;

    /** @var Handler<TResult, TMessage> */
    private readonly Handler $handler;

    /** @var list<Middleware> */
    private array $middlewares;

    private bool $handled = false;

    /**
     * @param Envelop<TResult, TMessage> $envelop
     * @param Handler<TResult, TMessage> $handler
     * @param list<Middleware> $middlewares
     */
    public function __construct(Envelop $envelop, Handler $handler, array $middlewares)
    {
        $this->envelop = $envelop;
        $this->handler = $handler;
        $this->middlewares = $middlewares;
    }

    /**
     * @template TTResult
     * @template TTMessage of Message<TTResult>
     * @param Envelop<TTResult, TTMessage> $envelop
     * @param Handler<TTResult, TTMessage> $handler
     * @param iterable<Middleware> $middlewares
     * @return (TTResult is void ? null : TTResult)
     */
    public static function handle(Envelop $envelop, Handler $handler, iterable $middlewares): mixed
    {
        $middlewares = $middlewares instanceof \Traversable ? iterator_to_array($middlewares, false) : array_values($middlewares);

        return (new self($envelop, $handler, $middlewares))->continue();
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
            return $middleware->handle($this->envelop, $this);
        }

        $this->handled = true;

        return $this->handler->handle($this->envelop);
    }
}

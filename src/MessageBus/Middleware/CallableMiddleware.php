<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\Middleware;

use Kenny1911\SisyphBus\Message\Message;
use Kenny1911\SisyphBus\MessageBus\MessageContext;

/**
 * @template TResult
 * @template TMessage of Message<TResult>
 */
final class CallableMiddleware implements Middleware
{
    /** @var callable(MessageContext<TResult, TMessage>, Pipeline<TResult, TMessage>): TResult */
    private mixed $callable;

    /**
     * @param callable(MessageContext<TResult, TMessage>, Pipeline<TResult, TMessage>): TResult $callable
     */
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * @param MessageContext<TResult, TMessage> $messageContext
     * @param Pipeline<TResult, TMessage> $pipeline
     */
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        return ($this->callable)($messageContext, $pipeline);
    }
}

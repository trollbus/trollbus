<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\Handler;

use Kenny1911\SisyphBus\Message\Message;
use Kenny1911\SisyphBus\MessageBus\Handler;
use Kenny1911\SisyphBus\MessageBus\MessageContext;

/**
 * @template TResult
 * @template TMessage of Message<TResult>
 * @implements Handler<TResult, TMessage>
 */
final class CallableHandler implements Handler
{
    /** @var callable(MessageContext<TResult, TMessage>): TResult */
    private mixed $callable;

    /**
     * @param callable(MessageContext<TResult, TMessage>): TResult $handler
     */
    public function __construct(callable $handler)
    {
        $this->callable = $handler;
    }

    public function handle(MessageContext $messageContext): mixed
    {
        return ($this->callable)($messageContext);
    }
}

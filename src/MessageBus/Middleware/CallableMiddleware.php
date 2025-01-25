<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Middleware;

use Trollbus\Message\Message;
use Trollbus\MessageBus\MessageContext;

/**
 * @template TResult
 * @template TMessage of Message<TResult>
 */
final class CallableMiddleware implements Middleware
{
    /**
     * @param callable(MessageContext<TResult, TMessage>, Pipeline<TResult, TMessage>): TResult $callable
     */
    public function __construct(
        private mixed $callable,
    ) {}

    /**
     * @param MessageContext<TResult, TMessage> $messageContext
     * @param Pipeline<TResult, TMessage> $pipeline
     */
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        return ($this->callable)($messageContext, $pipeline);
    }
}

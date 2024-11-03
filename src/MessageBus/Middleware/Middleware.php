<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\Middleware;

use Kenny1911\SisyphBus\Message\Message;

interface Middleware
{
    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param TMessage $message
     * @param Pipeline<TResult, TMessage> $pipeline
     * @return (TResult is void ? null : TResult)
     */
    public function handle(Message $message, Pipeline $pipeline): mixed;
}

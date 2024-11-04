<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\Middleware;

use Kenny1911\SisyphBus\Message\Message;
use Kenny1911\SisyphBus\MessageBus\Envelop;

interface Middleware
{
    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param Envelop<TResult, TMessage> $envelop
     * @param Pipeline<TResult, TMessage> $pipeline
     * @return (TResult is void ? null : TResult)
     */
    public function handle(Envelop $envelop, Pipeline $pipeline): mixed;
}

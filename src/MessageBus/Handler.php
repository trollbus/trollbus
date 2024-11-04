<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus;

use Kenny1911\SisyphBus\Message\Message;

/**
 * @template TResult
 * @template TMessage of Message<TResult>
 */
interface Handler
{
    /**
     * @param Envelop<TResult, TMessage> $envelop
     * @return (TResult is void ? null : TResult)
     */
    public function handle(Envelop $envelop): mixed;
}

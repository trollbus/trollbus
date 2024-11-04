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
     * @param MessageContext<TResult, TMessage> $messageContext
     * @return (TResult is void ? null : TResult)
     */
    public function handle(MessageContext $messageContext): mixed;
}

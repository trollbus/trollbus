<?php

declare(strict_types=1);

namespace Trollbus\MessageBus;

use Trollbus\Message\Message;

/**
 * @template TResult
 * @template TMessage of Message<TResult>
 * @extends ReadonlyHandler<TResult, TMessage>
 */
interface Handler extends ReadonlyHandler
{
    /**
     * @return non-empty-string
     */
    public function id(): string;

    /**
     * @param MessageContext<TResult, TMessage> $messageContext
     * @return (TResult is void ? null : TResult)
     */
    public function handle(MessageContext $messageContext): mixed;
}

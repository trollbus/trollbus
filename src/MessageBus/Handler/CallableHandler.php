<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Handler;

use Trollbus\Message\Message;
use Trollbus\MessageBus\Handler;
use Trollbus\MessageBus\MessageContext;

/**
 * @template TResult
 * @template TMessage of Message<TResult>
 * @implements Handler<TResult, TMessage>
 */
final class CallableHandler implements Handler
{
    /**
     * @param non-empty-string $id
     * @param callable(TMessage=, MessageContext<TResult, TMessage>=): TResult $handler
     */
    public function __construct(
        private readonly string $id,
        private readonly mixed $handler,
    ) {}

    #[\Override]
    public function id(): string
    {
        return $this->id;
    }

    #[\Override]
    public function handle(MessageContext $messageContext): mixed
    {
        return ($this->handler)($messageContext->getMessage(), $messageContext);
    }
}

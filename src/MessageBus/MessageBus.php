<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus;

use Kenny1911\SisyphBus\Message\Message;

final class MessageBus
{
    private readonly HandlerRegistry $handlerRegistry;

    public function __construct(HandlerRegistry $handlerRegistry)
    {
        $this->handlerRegistry = $handlerRegistry;
    }

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param TMessage $message
     * @return (TResult is void ? null : TResult)
     */
    public function dispatch(Message $message): mixed
    {
        return $this->handlerRegistry->get($message::class)->handle($message);
    }
}

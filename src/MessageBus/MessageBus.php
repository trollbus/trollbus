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
     * @param TMessage|Envelop<TResult, TMessage> $messageOrEnvelop
     * @return (TResult is void ? null : TResult)
     */
    public function dispatch(Message|Envelop $messageOrEnvelop): mixed
    {
        $envelop = Envelop::wrap($messageOrEnvelop);

        return $this->handlerRegistry->get($envelop->message::class)->handle($envelop);
    }
}

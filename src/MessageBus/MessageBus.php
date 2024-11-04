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
        return $this->handleContext($this->startContext($messageOrEnvelop));
    }

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param TMessage|Envelop<TResult, TMessage> $messageOrEnvelop
     * @return MessageContext<TResult, TMessage>
     */
    public function startContext(Message|Envelop $messageOrEnvelop): MessageContext
    {
        return MessageContext::start($this, $messageOrEnvelop);
    }

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param MessageContext<TResult, TMessage> $messageContext
     * @return (TResult is void ? null : TResult)
     */
    public function handleContext(MessageContext $messageContext): mixed
    {
        return $this->handlerRegistry->get($messageContext->getMessageClass())->handle($messageContext);
    }
}

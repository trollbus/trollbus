<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus;

use Kenny1911\SisyphBus\Message\Message;
use Kenny1911\SisyphBus\MessageBus\HandlerRegistry\ArrayHandlerRegistry;
use Kenny1911\SisyphBus\MessageBus\Middleware\Middleware;
use Kenny1911\SisyphBus\MessageBus\Middleware\Pipeline;

final class MessageBus
{
    private readonly HandlerRegistry $handlerRegistry;

    /** @var iterable<Middleware> */
    private readonly iterable $middlewares;

    /**
     * @param iterable<Middleware> $middlewares
     */
    public function __construct(HandlerRegistry $handlerRegistry = new ArrayHandlerRegistry(), iterable $middlewares = [])
    {
        $this->handlerRegistry = $handlerRegistry;
        $this->middlewares = $middlewares;
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
        return Pipeline::handle(
            $messageContext,
            $this->handlerRegistry->get($messageContext->getMessageClass()),
            $this->middlewares,
        );
    }
}

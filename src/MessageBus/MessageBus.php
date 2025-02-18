<?php

declare(strict_types=1);

namespace Trollbus\MessageBus;

use Trollbus\Message\Message;
use Trollbus\MessageBus\HandlerRegistry\ClassStringMapHandlerRegistry;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;

final class MessageBus
{
    /**
     * @param iterable<Middleware> $middlewares
     */
    public function __construct(
        private readonly HandlerRegistry $handlerRegistry = new ClassStringMapHandlerRegistry(),
        private readonly iterable $middlewares = [],
    ) {}

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param TMessage|Envelope<TResult, TMessage> $messageOrEnvelop
     * @return (TResult is void ? null : TResult)
     */
    public function dispatch(Message|Envelope $messageOrEnvelop): mixed
    {
        return $this->handleContext($this->startContext($messageOrEnvelop));
    }

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param TMessage|Envelope<TResult, TMessage> $messageOrEnvelop
     * @return MessageContext<TResult, TMessage>
     */
    public function startContext(Message|Envelope $messageOrEnvelop): MessageContext
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

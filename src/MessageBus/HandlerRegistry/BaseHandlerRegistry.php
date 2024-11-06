<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\HandlerRegistry;

use Kenny1911\SisyphBus\Message\Event;
use Kenny1911\SisyphBus\Message\Message;
use Kenny1911\SisyphBus\MessageBus\Handler;
use Kenny1911\SisyphBus\MessageBus\Handler\CallableHandler;
use Kenny1911\SisyphBus\MessageBus\HandlerRegistry;

abstract class BaseHandlerRegistry implements HandlerRegistry
{
    private ?Handler $nullEventHandler = null;

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param class-string<TMessage> $messageClass
     * @return Handler<TResult, TMessage>
     * @throws HandlerNotFound
     */
    final public function get(string $messageClass): Handler
    {
        $handler = $this->find($messageClass);

        if (null !== $handler) {
            return $handler;
        }

        if (is_subclass_of($messageClass, Event::class)) {
            /** @var CallableHandler<TResult, TMessage> */
            return $this->nullEventHandler ?? new CallableHandler('null event handler', static fn(): mixed => null);
        }

        throw new HandlerNotFound('Message handler not found.');
    }

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param class-string<TMessage> $messageClass
     * @return Handler<TResult, TMessage>|null
     */
    abstract protected function find(string $messageClass): ?Handler;
}

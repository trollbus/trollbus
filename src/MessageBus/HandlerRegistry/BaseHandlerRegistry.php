<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\HandlerRegistry;

use Trollbus\Message\Event;
use Trollbus\Message\Message;
use Trollbus\MessageBus\Handler;
use Trollbus\MessageBus\Handler\CallableHandler;
use Trollbus\MessageBus\HandlerRegistry;

abstract class BaseHandlerRegistry implements HandlerRegistry
{
    private ?Handler $nullEventHandler = null;

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     *
     * @param class-string<TMessage> $messageClass
     *
     * @return Handler<TResult, TMessage>
     *
     * @throws HandlerNotFound
     */
    #[\Override]
    final public function get(string $messageClass): Handler
    {
        $handler = $this->find($messageClass);

        if (null !== $handler) {
            return $handler;
        }

        if (is_subclass_of($messageClass, Event::class)) {
            /** @var CallableHandler<TResult, TMessage> */
            return $this->nullEventHandler ??= new CallableHandler('null event handler', static fn(): mixed => null);
        }

        throw new HandlerNotFound('Message handler not found.');
    }

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     *
     * @param class-string<TMessage> $messageClass
     *
     * @return Handler<TResult, TMessage>|null
     */
    abstract protected function find(string $messageClass): ?Handler;
}

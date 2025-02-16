<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\HandlerRegistry;

use Trollbus\Message\Message;
use Trollbus\MessageBus\Handler;

final class ClassStringMap
{
    private array $messageClassToHandlerMap = [];

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     *
     * @param class-string<TMessage> $messageClass
     * @param Handler<TResult, TMessage> $handler
     */
    public static function createWith(string $messageClass, Handler $handler): self
    {
        return (new self())->with($messageClass, $handler);
    }

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     *
     * @param class-string<TMessage> $messageClass
     *
     * @return Handler<TResult, TMessage>|null
     */
    public function find(string $messageClass): ?Handler
    {
        /** @var Handler<TResult, TMessage>|null */
        return $this->messageClassToHandlerMap[$messageClass] ?? null;
    }

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     *
     * @param class-string<TMessage> $messageClass
     * @param Handler<TResult, TMessage> $handler
     */
    public function with(string $messageClass, Handler $handler): self
    {
        $cloned = clone $this;
        $cloned->messageClassToHandlerMap[$messageClass] = $handler;

        return $cloned;
    }
}

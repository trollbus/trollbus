<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\HandlerRegistry;

use Trollbus\Message\Message;
use Trollbus\MessageBus\Handler;

final class ArrayHandlerRegistry extends BaseHandlerRegistry
{
    /**
     * Class string map of message class to message handler.
     */
    private readonly array $messageClassToHandler;

    /**
     * @param array $messageClassToHandler
     *   Map of message handlers, where key if class name of Message<TResult> and value is message Handler<TResult, Message<TResult>> instance
     */
    public function __construct(array $messageClassToHandler = [])
    {
        $this->messageClassToHandler = $messageClassToHandler;
    }

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     *
     * @param class-string<TMessage> $messageClass
     *
     * @return Handler<TResult, TMessage>|null
     */
    protected function find(string $messageClass): ?Handler
    {
        /** @var Handler<TResult, TMessage>|null */
        return $this->messageClassToHandler[$messageClass] ?? null;
    }
}

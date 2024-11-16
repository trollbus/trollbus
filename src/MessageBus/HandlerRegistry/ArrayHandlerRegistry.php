<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\HandlerRegistry;

use Kenny1911\SisyphBus\Message\Message;
use Kenny1911\SisyphBus\MessageBus\Handler;
use Kenny1911\SisyphBus\MessageBus\ReadonlyHandler;

final class ArrayHandlerRegistry extends BaseHandlerRegistry
{
    /**
     * Class string map of message class to message handler.
     *
     * @var array<class-string<Message>, Handler>
     */
    private readonly array $messageClassToHandler;

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param array<class-string<TMessage>, ReadonlyHandler<TResult, TMessage>> $messageClassToHandler
     */
    public function __construct(array $messageClassToHandler = [])
    {
        /** @var array<class-string<Message>, Handler> $messageClassToHandler */
        $this->messageClassToHandler = $messageClassToHandler;
    }

    protected function find(string $messageClass): ?Handler
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->messageClassToHandler[$messageClass] ?? null;
    }
}

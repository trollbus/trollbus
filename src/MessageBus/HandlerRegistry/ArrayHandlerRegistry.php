<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\HandlerRegistry;

use Trollbus\Message\Message;
use Trollbus\MessageBus\Handler;
use Trollbus\MessageBus\ReadonlyHandler;

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

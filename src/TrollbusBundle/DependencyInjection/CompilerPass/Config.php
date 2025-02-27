<?php

declare(strict_types=1);

namespace Trollbus\TrollbusBundle\DependencyInjection\CompilerPass;

use Trollbus\Message\Message;

final class Config
{
    /** @var non-empty-string */
    public readonly string $messageBus;

    /** @var non-empty-string */
    public readonly string $middlewareTag;

    /** @var non-empty-string */
    public readonly string $handlerTag;

    /** @var non-empty-string */
    public readonly string $handlerTagMessage;

    /** @var non-empty-string */
    public readonly string $handlerTagId;

    /** @var non-empty-string */
    public readonly string $handlerTagMethod;

    /** @var non-empty-string */
    public readonly string $handlerTagMiddlewares;

    /** @var non-empty-string */
    public readonly string $handlerTagAsync;

    /** @var non-empty-string */
    public readonly string $handlerMiddlewareTag;

    /**
     * @param non-empty-lowercase-string $servicePrefix
     */
    public function __construct(
        string $servicePrefix = 'message_bus',
    ) {
        $this->messageBus = $servicePrefix;
        $this->middlewareTag = $servicePrefix . '.middleware';
        $this->handlerTag = $servicePrefix . '.handler';
        $this->handlerTagMessage = 'message';
        $this->handlerTagId = 'handlerId';
        $this->handlerTagMethod = 'method';
        $this->handlerTagMiddlewares = 'middlewares';
        $this->handlerTagAsync = 'async';
        $this->handlerMiddlewareTag = $servicePrefix . '.handler_middleware';
    }

    /**
     * @param class-string<Message> $messageClass
     *
     * @return non-empty-string
     */
    public function handlerId(string $messageClass, int $index): string
    {
        return $this->messageBus.'.handler.'.$messageClass.'.'.$index;
    }
}

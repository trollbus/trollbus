<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\HandlerRegistry;

use Kenny1911\SisyphBus\MessageBus\Handler;
use Kenny1911\SisyphBus\MessageBus\HandlerRegistry;

final class ArrayHandlerRegistry extends BaseHandlerRegistry
{
    /** @var array<class-string<Handler>, Handler> */
    private array $handlers;

    /**
     * @param array<class-string<Handler>, Handler> $handlers
     */
    public function __construct(array $handlers = [])
    {
        $this->handlers = $handlers;
    }

    protected function find(string $messageClass): ?Handler
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->handlers[$messageClass] ?? null;
    }
}

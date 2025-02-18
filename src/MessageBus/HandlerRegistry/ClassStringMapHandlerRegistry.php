<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\HandlerRegistry;

use Trollbus\MessageBus\Handler;

final class ClassStringMapHandlerRegistry extends BaseHandlerRegistry
{
    public function __construct(
        private readonly ClassStringMap $messageClassToHandlerMap = new ClassStringMap(),
    ) {}

    #[\Override]
    protected function find(string $messageClass): ?Handler
    {
        return $this->messageClassToHandlerMap->find($messageClass);
    }
}

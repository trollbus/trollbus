<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\HandlerRegistry;

use Kenny1911\SisyphBus\MessageBus\Handler\CallableHandler;
use Kenny1911\SisyphBus\MessageBus\HandlerRegistry;
use Kenny1911\SisyphBus\MessageBus\HandlerRegistryTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(className: ArrayHandlerRegistry::class)]
#[UsesClass(className: CallableHandler::class)]
final class ArrayHandlerRegistryTest extends HandlerRegistryTestCase
{
    protected function createHandlerRegistry(array $handlers): HandlerRegistry
    {
        return new ArrayHandlerRegistry($handlers);
    }
}

<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\HandlerRegistry;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Trollbus\MessageBus\Handler\CallableHandler;
use Trollbus\MessageBus\HandlerRegistry;
use Trollbus\MessageBus\HandlerRegistry\ArrayHandlerRegistry;
use Trollbus\Tests\MessageBus\HandlerRegistryTestCase;

#[CoversClass(className: ArrayHandlerRegistry::class)]
#[UsesClass(className: CallableHandler::class)]
final class ArrayHandlerRegistryTest extends HandlerRegistryTestCase
{
    protected function createHandlerRegistry(array $handlers): HandlerRegistry
    {
        return new ArrayHandlerRegistry($handlers);
    }
}

<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\HandlerRegistry;

use Trollbus\MessageBus\HandlerRegistry;
use Trollbus\MessageBus\HandlerRegistry\ArrayHandlerRegistry;
use Trollbus\Tests\MessageBus\HandlerRegistryTestCase;

final class ArrayHandlerRegistryTest extends HandlerRegistryTestCase
{
    protected function createHandlerRegistry(array $handlers): HandlerRegistry
    {
        return new ArrayHandlerRegistry($handlers);
    }
}

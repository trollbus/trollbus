<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus;

use PHPUnit\Framework\TestCase;
use Trollbus\Message\Message;
use Trollbus\MessageBus\Handler;
use Trollbus\MessageBus\Handler\CallableHandler;
use Trollbus\MessageBus\HandlerRegistry;
use Trollbus\MessageBus\HandlerRegistry\HandlerNotFound;
use Trollbus\Tests\Message\TestCommand;
use Trollbus\Tests\Message\TestEvent;

abstract class HandlerRegistryTestCase extends TestCase
{
    public function testGet(): void
    {
        /** @var CallableHandler<void, TestCommand> $handler */
        $handler = new CallableHandler('test', static fn() => null);
        $handlerRegistry = $this->createHandlerRegistry([TestCommand::class => $handler]);

        self::assertSame($handler, $handlerRegistry->get(TestCommand::class));
    }

    public function testGetHandlerNotFound(): void
    {
        $this->expectException(HandlerNotFound::class);

        $handlerRegistry = $this->createHandlerRegistry([]);

        $handlerRegistry->get(TestCommand::class);
    }

    public function testGetNullEventHandler(): void
    {
        $handlerRegistry = $this->createHandlerRegistry([]);

        $handler = $handlerRegistry->get(TestEvent::class);

        self::assertInstanceOf(CallableHandler::class, $handler);
        self::assertSame('null event handler', $handler->id());

        // Check memoization of null event handler
        self::assertSame($handler, $handlerRegistry->get(TestEvent::class));
    }

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param array<class-string<TMessage>, Handler<TResult, TMessage>> $handlers
     */
    abstract protected function createHandlerRegistry(array $handlers): HandlerRegistry;
}

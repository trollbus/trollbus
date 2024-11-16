<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus;

use Kenny1911\SisyphBus\Message\Message;
use Kenny1911\SisyphBus\Message\TestCommand;
use Kenny1911\SisyphBus\Message\TestEvent;
use Kenny1911\SisyphBus\MessageBus\Handler\CallableHandler;
use Kenny1911\SisyphBus\MessageBus\HandlerRegistry\HandlerNotFound;
use PHPUnit\Framework\TestCase;

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

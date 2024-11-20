<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus;

use Kenny1911\SisyphBus\Message\Message;
use Kenny1911\SisyphBus\Message\TestCommand;
use Kenny1911\SisyphBus\Message\TestEvent;
use Kenny1911\SisyphBus\Message\TestQuery;
use Kenny1911\SisyphBus\MessageBus\Handler\CallableHandler;
use Kenny1911\SisyphBus\MessageBus\Handler\EventHandler;
use Kenny1911\SisyphBus\MessageBus\HandlerRegistry\ArrayHandlerRegistry;
use Kenny1911\SisyphBus\MessageBus\HandlerRegistry\BaseHandlerRegistry;
use Kenny1911\SisyphBus\MessageBus\Middleware\CallableMiddleware;
use Kenny1911\SisyphBus\MessageBus\Middleware\Pipeline;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MessageBus::class)]
#[UsesClass(Envelope::class)]
#[UsesClass(ArrayHandlerRegistry::class)]
#[UsesClass(BaseHandlerRegistry::class)]
#[UsesClass(CallableHandler::class)]
#[UsesClass(EventHandler::class)]
#[UsesClass(MessageContext::class)]
#[UsesClass(Pipeline::class)]
#[UsesClass(ReadonlyMessageContext::class)]
#[UsesClass(CallableMiddleware::class)]
final class MessageBusTest extends TestCase
{
    public function testDispatchCommand(): void
    {
        $handled = false;
        $handler = new CallableHandler('test', static function () use (&$handled): void {
            $handled = true;
        });
        $messageBus = new MessageBus(
            new ArrayHandlerRegistry([
                TestCommand::class => $handler,
            ]),
        );

        $messageBus->dispatch(new TestCommand());

        self::assertTrue($handled);
    }

    public function testDispatchQuery(): void
    {
        $handled = false;
        /** @var CallableHandler<bool, TestQuery> $handler */
        $handler = new CallableHandler('test', static function () use (&$handled): bool {
            $handled = true;

            return true;
        });

        $messageBus = new MessageBus(
            new ArrayHandlerRegistry([
                TestQuery::class => $handler,
            ]),
        );

        self::assertTrue($messageBus->dispatch(new TestQuery()));
        self::assertTrue($handled);
    }

    public function testDispatchEvent(): void
    {
        $handled1 = false;
        /** @var CallableHandler<void, TestEvent> $handler1 */
        $handler1 = new CallableHandler('test1', static function () use (&$handled1): void {
            $handled1 = true;
        });

        $handled2 = false;
        /** @var CallableHandler<void, TestEvent> $handler2 */
        $handler2 = new CallableHandler('test2', static function () use (&$handled2): void {
            $handled2 = true;
        });

        $eventHandler = new EventHandler([$handler1, $handler2]);

        $messageBus = new MessageBus(
            new ArrayHandlerRegistry([
                TestEvent::class => $eventHandler,
            ]),
        );

        $messageBus->dispatch(new TestEvent());

        self::assertTrue($handled1);
        self::assertTrue($handled2);
    }

    public function testDispatchWithMiddlewares(): void
    {
        $handled = [];
        $middleware1 = new CallableMiddleware(static function (MessageContext $messageContext, Pipeline $pipeline) use (&$handled): mixed {
            $handled[] = 'middleware1';

            return $pipeline->continue();
        });
        $middleware2 = new CallableMiddleware(static function (MessageContext $messageContext, Pipeline $pipeline) use (&$handled): mixed {
            $handled[] = 'middleware2';

            return $pipeline->continue();
        });
        $handler = new CallableHandler('test', static function () use (&$handled): void {
            $handled[] = 'handler';
        });

        $messageBus = new MessageBus(
            new ArrayHandlerRegistry([
                TestCommand::class => $handler,
            ]),
            [$middleware1, $middleware2],
        );

        $messageBus->dispatch(new TestCommand());

        self::assertSame(['middleware1', 'middleware2', 'handler'], $handled);
    }

    public function testDispatchWithNestedPipelines(): void
    {
        $handled = false;
        /** @var CallableHandler<void, TestCommand> $handler */
        $handler = new CallableHandler(
            'test-command',
            static function (Message $message, MessageContext $messageContext) use (&$handled): mixed {
                $handled = true;
                $messageContext->dispatch(new TestEvent());

                return null;
            },
        );

        $nestedHandled = false;
        /** @var CallableHandler<void, TestEvent> $handler */
        $nestedHandler = new CallableHandler('test-event', static function () use (&$nestedHandled): void {
            $nestedHandled = true;
        });

        $messageBus = new MessageBus(
            new ArrayHandlerRegistry([
                TestCommand::class => $handler,
                TestEvent::class => $nestedHandler,
            ]),
        );

        $messageBus->dispatch(new TestCommand());

        self::assertTrue($handled);
        self::assertTrue($nestedHandled);
    }
}

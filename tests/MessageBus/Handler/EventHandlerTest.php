<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\Handler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Trollbus\MessageBus\Envelope;
use Trollbus\MessageBus\Handler\CallableHandler;
use Trollbus\MessageBus\Handler\EventHandler;
use Trollbus\MessageBus\HandlerRegistry\ArrayHandlerRegistry;
use Trollbus\MessageBus\MessageBus;
use Trollbus\MessageBus\MessageContext;
use Trollbus\Tests\Message\TestEvent;

#[CoversClass(className: EventHandler::class)]
#[UsesClass(className: CallableHandler::class)]
#[UsesClass(className: Envelope::class)]
#[UsesClass(className: MessageContext::class)]
#[UsesClass(className: MessageBus::class)]
#[UsesClass(className: ArrayHandlerRegistry::class)]
final class EventHandlerTest extends TestCase
{
    /**
     * @throws \JsonException
     */
    public function test(): void
    {
        $handled1 = false;
        /** @var CallableHandler<void, TestEvent> $handler1 */
        $handler1 = new CallableHandler('handler1', static function () use (&$handled1): void {
            $handled1 = true;
        });

        $handled2 = false;
        /** @var CallableHandler<void, TestEvent> $handler2 */
        $handler2 = new CallableHandler('handler2', static function () use (&$handled2): void {
            $handled2 = true;
        });

        $eventHandler = new EventHandler([$handler1, $handler2]);
        $messageContext = MessageContext::start(new MessageBus(), new TestEvent());

        $eventHandler->handle($messageContext);

        self::assertSame('["handler1","handler2"]', $eventHandler->id());
        self::assertTrue($handled1);
        self::assertTrue($handled2);
    }
}

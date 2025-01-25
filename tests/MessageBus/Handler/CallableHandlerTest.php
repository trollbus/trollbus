<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\Handler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Trollbus\MessageBus\Envelope;
use Trollbus\MessageBus\Handler\CallableHandler;
use Trollbus\MessageBus\HandlerRegistry\ArrayHandlerRegistry;
use Trollbus\MessageBus\MessageBus;
use Trollbus\MessageBus\MessageContext;
use Trollbus\Tests\Message\TestCommand;

#[CoversClass(className: CallableHandler::class)]
#[UsesClass(className: MessageContext::class)]
#[UsesClass(className: MessageBus::class)]
#[UsesClass(className: Envelope::class)]
#[UsesClass(className: ArrayHandlerRegistry::class)]
final class CallableHandlerTest extends TestCase
{
    public function test(): void
    {
        $handled = false;
        $handler = new CallableHandler('test', static function () use (&$handled): void {
            $handled = true;
        });
        $messageContext = MessageContext::start(new MessageBus(), new TestCommand());
        $handler->handle($messageContext);

        self::assertSame('test', $handler->id());
        self::assertTrue($handled);
    }
}

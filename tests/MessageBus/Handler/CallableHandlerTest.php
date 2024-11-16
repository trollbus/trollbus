<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\Handler;

use Kenny1911\SisyphBus\Message\TestCommand;
use Kenny1911\SisyphBus\MessageBus\Envelop;
use Kenny1911\SisyphBus\MessageBus\HandlerRegistry\ArrayHandlerRegistry;
use Kenny1911\SisyphBus\MessageBus\MessageBus;
use Kenny1911\SisyphBus\MessageBus\MessageContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(className: CallableHandler::class)]
#[UsesClass(className: MessageContext::class)]
#[UsesClass(className: MessageBus::class)]
#[UsesClass(className: Envelop::class)]
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

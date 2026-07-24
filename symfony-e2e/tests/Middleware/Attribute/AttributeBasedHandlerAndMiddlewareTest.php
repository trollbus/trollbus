<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Attribute;

use App\Middleware\Attribute\AttributeBasedHandlerAndMiddleware;
use App\Middleware\Attribute\SomeCommand;
use App\Tests\KernelTestCase;

final class AttributeBasedHandlerAndMiddlewareTest extends KernelTestCase
{
    public function test(): void
    {
        self::bootKernel();
        $bus = self::getMessageBus();

        $bus->dispatch($command = new SomeCommand());

        $handler = self::getContainer()->get(AttributeBasedHandlerAndMiddleware::class);
        \assert($handler instanceof AttributeBasedHandlerAndMiddleware);

        self::assertSame(
            ['globalMiddleware' => $command, 'middleware' => $command, 'handler' => $command],
            $handler->pullCallChain(),
        );
    }
}

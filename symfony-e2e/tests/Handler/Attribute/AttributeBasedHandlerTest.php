<?php

declare(strict_types=1);

namespace App\Tests\Handler\Attribute;

use App\Handler\Attribute\AttributeBasedHandler;
use App\Handler\Attribute\SomeCommand;
use App\Tests\KernelTestCase;

final class AttributeBasedHandlerTest extends KernelTestCase
{
    public function test(): void
    {
        self::bootKernel();

        $handler = self::getContainer()->get(AttributeBasedHandler::class);
        \assert($handler instanceof AttributeBasedHandler);

        $bus = self::getMessageBus();

        $bus->dispatch(new SomeCommand('test'));

        self::assertSame(
            ['SomeCommand: test', 'SomeEvent: 1', 'SomeEvent: 2'],
            $handler->pullCalledMessages(),
        );
    }
}

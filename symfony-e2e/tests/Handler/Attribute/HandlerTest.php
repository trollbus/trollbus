<?php

declare(strict_types=1);

namespace App\Tests\Handler\Attribute;

use App\Handler\Attribute\SomeCommand;
use App\Handler\Attribute\SomeHandler;
use App\Tests\KernelTestCase;

final class HandlerTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function test(): void
    {
        $handler = self::getContainer()->get(SomeHandler::class);
        \assert($handler instanceof SomeHandler);

        $bus = self::getMessageBus();

        $bus->dispatch(new SomeCommand('test'));

        self::assertSame(
            ['SomeCommand: test', 'SomeEvent: 1', 'SomeEvent: 2'],
            $handler->pullCalledMessages(),
        );
    }
}

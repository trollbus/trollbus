<?php

declare(strict_types=1);

namespace App\Tests\Handler\Attribute;

use App\Handler\Attribute\AttributeBasedHandler;
use App\Handler\Attribute\SomeCommand;
use App\Middleware\Attribute\MessageTrack\MessageTrack;
use App\Tests\KernelTestCase;
use Trollbus\MessageBus\Envelope;

final class AttributeBasedHandlerTest extends KernelTestCase
{
    public function test(): void
    {
        self::bootKernel();

        $handler = self::getContainer()->get(AttributeBasedHandler::class);
        \assert($handler instanceof AttributeBasedHandler);

        $bus = self::getMessageBus();

        $result = $bus->dispatch($envelope = Envelope::wrap(
            new SomeCommand('test'),
            new MessageTrack(),
        ));

        // Assert command result
        self::assertTrue($result);

        // Assert called event listeners after command
        self::assertSame(
            ['SomeCommand: test', 'SomeEvent: 1', 'SomeEvent: 2'],
            $handler->pullCalledMessages(),
        );

        // Assert middlewares
        self::assertSame(
            ['global', 'handler'],
            $envelope->getStamp(MessageTrack::class)?->tracks,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Handler\Attribute;

use App\Handler\Attribute\AttributeBasedHandler;
use App\Handler\Attribute\SomeCommand;
use App\Handler\Attribute\SomeStamp;
use App\Middleware\Attribute\MessageTrack\MessageTrack;
use App\Middleware\EnvelopeCollector\EnvelopeCollector;
use App\Tests\KernelTestCase;
use Trollbus\MessageBus\Envelope;

final class AttributeBasedHandlerTest extends KernelTestCase
{
    public function test(): void
    {
        self::bootKernel();

        $handler = self::getContainer()->get(AttributeBasedHandler::class);
        \assert($handler instanceof AttributeBasedHandler);

        $envelopeCollector = self::getContainer()->get(EnvelopeCollector::class);
        \assert($envelopeCollector instanceof EnvelopeCollector);

        $bus = self::getMessageBus();

        $result = $bus->dispatch(Envelope::wrap(
            new SomeCommand('test'),
            new MessageTrack(),
        ));

        $envelope = $envelopeCollector->pull();
        self::assertInstanceOf(Envelope::class, $envelope);

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

        self::assertTrue(
            $envelope->hasStamp(SomeStamp::class),
        );
    }
}

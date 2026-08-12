<?php

declare(strict_types=1);

namespace Trollbus\Tests\Unit\PgmqTransport;

use PHPUnit\Framework\TestCase;
use Trollbus\MessageBus\Async\MessageType\OrigClassMessageTypeResolver;
use Trollbus\MessageBus\Async\ObjectNormalizer\PhpNativeSerializer;
use Trollbus\MessageBus\Envelope;
use Trollbus\MessageBus\MessageId\MessageId;
use Trollbus\PgmqTransport\DefaultPgmqEncoder;
use Trollbus\PgmqTransport\PgmqMessage;

final class DefaultPgmqEncoderTest extends TestCase
{
    private const EXPECTED_HEADER = '{"type":"Trollbus\\\Tests\\\Unit\\\PgmqTransport\\\SomeMessage"}';
    private const EXPECTED_BODY = '"O:28:\"Trollbus\\\MessageBus\\\Envelope\":2:{s:7:\"message\";O:45:\"Trollbus\\\Tests\\\Unit\\\PgmqTransport\\\SomeMessage\":1:{s:3:\"foo\";s:3:\"bar\";}s:6:\"stamps\";a:1:{s:39:\"Trollbus\\\MessageBus\\\MessageId\\\MessageId\";O:39:\"Trollbus\\\MessageBus\\\MessageId\\\MessageId\":1:{s:9:\"messageId\";s:1:\"1\";}}}"';

    private DefaultPgmqEncoder $encoder;

    protected function setUp(): void
    {
        $objectSerializer = new PhpNativeSerializer();
        $messageTypeResolver = new OrigClassMessageTypeResolver();
        $this->encoder = new DefaultPgmqEncoder(
            normalizer: $objectSerializer,
            denormalizer: $objectSerializer,
            messageTypeResolver: $messageTypeResolver,
        );
    }

    public function testEncode(): void
    {
        $envelope = Envelope::wrap(new SomeMessage(foo: 'bar'), new MessageId(messageId: '1'));
        $pgmqSendMessage = $this->encoder->encode($envelope);

        self::assertSame(self::EXPECTED_HEADER, $pgmqSendMessage->headerJson);
        self::assertSame(self::EXPECTED_BODY, $pgmqSendMessage->valueJson);
    }

    public function testDecode(): void
    {
        $pgmqMessage = new PgmqMessage(
            id: 1,
            readCount: 0,
            enqueuedAt: new \DateTimeImmutable(),
            value: self::EXPECTED_BODY,
            visibilityTimeout: new \DateTimeImmutable(),
            headers: self::EXPECTED_HEADER,
        );
        $envelope = $this->encoder->decode($pgmqMessage);
        $message = $envelope->message;

        self::assertCount(1, $envelope->stamps);
        self::assertSame('1', $envelope->getStamp(MessageId::class)?->messageId);
        self::assertInstanceOf(SomeMessage::class, $message);
        self::assertSame('bar', $message->foo);
    }
}

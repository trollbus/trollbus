<?php

declare(strict_types=1);

namespace Trollbus\PgmqTransport;

use Trollbus\MessageBus\Async\MessageType\MessageTypeResolver;
use Trollbus\MessageBus\Async\MessageType\UnsupportedMessageClass;
use Trollbus\MessageBus\Async\ObjectNormalizer\ObjectDenormalizer;
use Trollbus\MessageBus\Async\ObjectNormalizer\ObjectNormalizer;
use Trollbus\MessageBus\Envelope;

final class DefaultPgmqEncoder implements PgmqMessageEncoder, PgmqMessageDecoder
{
    private const TYPE_HEADER = 'type';

    public function __construct(
        private readonly ObjectNormalizer $normalizer,
        private readonly ObjectDenormalizer $denormalizer,
        private readonly MessageTypeResolver $messageTypeResolver,
    ) {}

    /**
     * @throws UnsupportedMessageClass
     */
    public function encode(Envelope $envelope): PgmqSendMessage
    {
        return new PgmqSendMessage(
            valueJson: json_encode($this->normalizer->normalize($envelope)),
            headerJson: json_encode([
                self::TYPE_HEADER => $this->messageTypeResolver->resolveType($envelope->getMessageClass()),
            ]),
        );
    }

    /**
     * @throws \JsonException
     */
    public function decode(PgmqMessage $pgmqMessage): Envelope
    {
        if (null === $pgmqMessage->headers) {
            throw new \RuntimeException('Can not resolve type of pgmq message. No headers.');
        }

        $headers = json_decode($pgmqMessage->headers, true, flags: JSON_THROW_ON_ERROR);

        if (!isset($headers[self::TYPE_HEADER])) {
            throw new \RuntimeException(\sprintf('Can not resolve type of pgmq message. No header "%s".', self::TYPE_HEADER));
        }

        $type = (string) $headers[self::TYPE_HEADER];

        if ('' === $type) {
            throw new \RuntimeException('Can not resolve type of pgmq message. Type is not specified.');
        }

        $class = $this->messageTypeResolver->resolveClass($type);

        $envelope = $this->denormalizer->denormalize(
            data: json_decode($pgmqMessage->value, flags: JSON_THROW_ON_ERROR),
            class: Envelope::class,
        );

        if (!$envelope instanceof Envelope) {
            throw new \RuntimeException(\sprintf(
                'Invalid envelope type. Expected "%s", actual "%s".',
                Envelope::class,
                get_debug_type($envelope),
            ));
        }

        if (!$envelope->message instanceof $class) {
            throw new \RuntimeException(\sprintf(
                'Invalid message type. Expected "%s", actual "%s".',
                $class,
                get_debug_type($envelope),
            ));
        }

        return $envelope;
    }
}

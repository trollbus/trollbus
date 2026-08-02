<?php

declare(strict_types=1);

namespace Trollbus\PgmqTransport;

use Trollbus\MessageBus\Async\Consumer;
use Trollbus\MessageBus\Async\Delay\Delay;
use Trollbus\MessageBus\Async\Exchange\Exchange;
use Trollbus\MessageBus\Async\TransportConsumer;
use Trollbus\MessageBus\Async\TransportPublisher;
use Trollbus\MessageBus\Async\TransportSetup;
use Trollbus\MessageBus\Envelope;

final class PgmqTransport implements TransportPublisher, TransportConsumer, TransportSetup
{
    public function __construct(
        private readonly PgmqDriver $driver,
        private readonly PgmqMessageEncoder $encoder,
        private readonly PgmqMessageDecoder $decoder,
    ) {}

    public function publish(array $envelopes): void
    {
        foreach ($envelopes as $envelope) {
            \assert($envelope instanceof Envelope);
            $topic = $envelope->getStamp(Exchange::class)?->exchange;

            // Skip, if exchange not specified.
            if (null === $topic) {
                continue;
            }

            $encodedMessage = $this->encoder->encode($envelope);
            $delay = (int) (($envelope->getStamp(Delay::class)?->milliseconds ?? 0) / 1_000);

            $this->driver->sendTopic(
                pattern: $topic,
                message: $encodedMessage->valueJson,
                headers: $encodedMessage->headerJson,
                delay: $delay,
            );
        }
    }

    public function runConsume(Consumer $consumer): \Closure
    {
        return $this->driver->consume($consumer->queue, function (PgmqMessage $message) use ($consumer): void {
            $envelope = $this->decoder->decode($message);
            $consumer->handle($envelope);
        });
    }

    public function disconnect(): void
    {
        $this->disconnect();
    }

    public function setup(array $exchangeToQueues): void
    {
        foreach ($exchangeToQueues as $topic => $queues) {
            foreach ($queues as $queue) {
                $this->driver->createQueue($queue);
                $this->driver->bindTopic($topic, $queue);
            }
        }
    }
}

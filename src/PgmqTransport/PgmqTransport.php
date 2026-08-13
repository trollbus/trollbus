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
use Trollbus\MessageBus\Transaction\TransactionProvider;

final class PgmqTransport implements TransportPublisher, TransportConsumer, TransportSetup
{
    public function __construct(
        private readonly PgmqDriver $driver,
        private readonly PgmqMessageEncoder $encoder,
        private readonly PgmqMessageDecoder $decoder,
        private readonly TransactionProvider $transactionProvider,
        private readonly bool $archive = false,
    ) {}

    public function publish(array $envelopes): void
    {
        foreach ($envelopes as $envelope) {
            \assert($envelope instanceof Envelope);
            $topic = $envelope->getStamp(Exchange::class)?->exchange;

            if (null === $topic) {
                throw new \RuntimeException('Can not resolve topic.');
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
            $this->transactionProvider->wrapInTransaction(function () use ($message, $consumer): void {
                $envelope = $this->decoder->decode($message);
                $consumer->handle($envelope);
                $this->driver->ack($consumer->queue, $message->id, $this->archive);
            });
        });
    }

    public function disconnect(): void
    {
        // Noop...
        // Connection with db can be used in other routines
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

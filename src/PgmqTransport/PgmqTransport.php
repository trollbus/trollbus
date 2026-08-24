<?php

declare(strict_types=1);

namespace Trollbus\PgmqTransport;

use Trollbus\MessageBus\Async\Consumer;
use Trollbus\MessageBus\Async\Delay\Delay;
use Trollbus\MessageBus\Async\Exchange\Exchange;
use Trollbus\MessageBus\Async\Retry\Retry;
use Trollbus\MessageBus\Async\TransportConsumer;
use Trollbus\MessageBus\Async\TransportPublisher;
use Trollbus\MessageBus\Async\TransportSetup;
use Trollbus\MessageBus\Envelope;
use Trollbus\MessageBus\Transaction\TransactionProvider;
use Trollbus\PgmqTransport\VisibilityTimeout\VisibilityTimeout;

final class PgmqTransport implements TransportPublisher, TransportConsumer, TransportSetup
{
    /**
     * @param non-empty-string $dealLettersQueue
     */
    public function __construct(
        private readonly PgmqDriver $driver,
        private readonly PgmqMessageEncoder $encoder,
        private readonly PgmqMessageDecoder $decoder,
        private readonly TransactionProvider $transactionProvider,
        private readonly string $dealLettersQueue = 'dead_letters',
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
        return $this->driver->consume($consumer->queue, function (PgmqMessage $pgmqMessage) use ($consumer): void {
            try {
                $envelope = $this->decoder->decode($pgmqMessage);
            } catch (\Throwable $exception) {
                $this->sendToDealLetterQueue($pgmqMessage, $consumer->queue, $exception);

                return;
            }

            try {
                $vt = $envelope->getStamp(VisibilityTimeout::class);

                $this->transactionProvider->wrapInTransaction(function () use ($pgmqMessage, $envelope, $vt, $consumer): void {
                    if (null !== $vt) {
                        $this->driver->setVisibilityTimeout(
                            queue: $consumer->queue,
                            msgId: $pgmqMessage->id,
                            visibilityTimeout: $vt->seconds,
                        );
                    }

                    $consumer->handle($envelope);
                    $this->driver->ack($consumer->queue, $pgmqMessage->id, $this->archive);
                });
            } catch (\Throwable $exception) {
                $retry = $envelope->getStamp(Retry::class);
                $timeout = $retry?->timeouts[$pgmqMessage->readCount - 1] ?? null;

                if (null === $timeout) {
                    $this->sendToDealLetterQueue($pgmqMessage, $consumer->queue, $exception);
                } else {
                    $this->driver->setVisibilityTimeout(
                        queue: $consumer->queue,
                        msgId: $pgmqMessage->id,
                        visibilityTimeout: $timeout,
                    );
                }
            }
        });
    }

    public function disconnect(): void
    {
        // Noop...
        // Connection with db can be used in other routines
    }

    public function setup(array $exchangeToQueues): void
    {
        $this->driver->createQueue($this->dealLettersQueue);

        foreach ($exchangeToQueues as $topic => $queues) {
            foreach ($queues as $queue) {
                if ($queue === $this->dealLettersQueue) {
                    throw new \LogicException(\sprintf('Reserved queue name "%s". It used for deal letters.', $queue));
                }

                $this->driver->createQueue($queue);
                $this->driver->bindTopic($topic, $queue);
            }
        }
    }

    private function sendToDealLetterQueue(PgmqMessage $pgmqMessage, string $origQueue, \Throwable $exception): void
    {
        $headers = null !== $pgmqMessage->headers ? (array) json_decode($pgmqMessage->headers, true) : [];
        $headers['_dlq'] = [
            'exception' => self::normalizeException($exception),
            'origQueue' => $origQueue,
        ];

        $this->transactionProvider->wrapInTransaction(function() use ($pgmqMessage, $origQueue, $headers): void {
            $this->driver->ack(queue: $origQueue, msgId: $pgmqMessage->id, archive: $this->archive);
            $this->driver->send(
                queue: $this->dealLettersQueue,
                message: $pgmqMessage->value,
                headers: json_encode($headers, JSON_THROW_ON_ERROR),
            );
        });
    }

    private static function normalizeException(\Throwable $e): array
    {
        $data = [
            'class' => $e::class,
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            // PGMQ rejects messages containing null-bytes.
            // $e->getTrace() returns an array with binary data that cannot be sent to the queue.
            // Use getTraceAsString() - returns a string without null-bytes.
            'trace' => $e->getTraceAsString(),
        ];

        if (null !== ($previous = $e->getPrevious())) {
            $data['previous'] = self::normalizeException($previous);
        }

        return $data;
    }
}

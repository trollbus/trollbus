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
        private readonly string $dealLettersQueue = 'deal_letters',
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
                $this->sendToDealLetterQueue($pgmqMessage, $exception);

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
                $timeout = $retry?->timeouts[$pgmqMessage->readCount] ?? null;

                if (null === $timeout) {
                    $this->transactionProvider->wrapInTransaction(function () use ($pgmqMessage, $consumer, $exception): void {
                        $this->driver->ack(queue: $consumer->queue, msgId: $pgmqMessage->id, archive: $this->archive);
                        $this->sendToDealLetterQueue($pgmqMessage, $exception);
                    });
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

    private function sendToDealLetterQueue(PgmqMessage $pgmqMessage, \Throwable $exception): void
    {
        $origHeaders = null !== $pgmqMessage->headers ? json_decode($pgmqMessage->headers, true) : null;
        $headers = [
            'orig' => $origHeaders,
            'exception' => self::normalizeException($exception),
        ];

        $this->driver->send(
            queue: $this->dealLettersQueue,
            message: $pgmqMessage->value,
            headers: json_encode($headers, JSON_THROW_ON_ERROR),
        );
    }

    private static function normalizeException(\Throwable $e): array
    {
        $data = [
            'class' => $e::class,
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTrace(),
        ];

        if (null !== ($previous = $e->getPrevious())) {
            $data['previous'] = self::normalizeException($previous);
        }

        return $data;
    }
}

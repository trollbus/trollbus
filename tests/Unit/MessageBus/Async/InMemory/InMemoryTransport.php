<?php

declare(strict_types=1);

namespace Trollbus\Tests\Unit\MessageBus\Async\InMemory;

use Revolt\EventLoop;
use Trollbus\MessageBus\Async\Consumer;
use Trollbus\MessageBus\Async\Exchange\Exchange;
use Trollbus\MessageBus\Async\TransportConsumer;
use Trollbus\MessageBus\Async\TransportPublisher;
use Trollbus\MessageBus\Async\TransportSetup;
use Trollbus\MessageBus\Envelope;

/**
 * Special in-memory async transport for unit tests.
 */
final class InMemoryTransport implements TransportPublisher, TransportConsumer, TransportSetup
{
    /** @var array<non-empty-string, list<non-empty-string>> */
    private array $exchangeToQueues = [];

    /** @var array<non-empty-string, list<Envelope>> Key-value, when key is queue name and value is published message envelope. */
    private array $publishedEnvelopes = [];

    public function publish(array $envelopes): void
    {
        foreach ($envelopes as $envelope) {
            $exchange = $envelope->getStamp(Exchange::class)?->exchange;

            if (null === $exchange) {
                continue;
            }

            $queues = $this->exchangeToQueues[$exchange] ?? [];

            foreach ($queues as $queue) {
                $this->publishedEnvelopes[$queue][] = $envelope;
            }
        }
    }

    public function runConsume(Consumer $consumer): \Closure
    {
        $isCancelled = false;

        if (!isset($this->publishedEnvelopes[$consumer->queue])) {
            $this->publishedEnvelopes[$consumer->queue] = [];
        }

        $callback = function () use ($consumer, &$isCancelled): void {
            while ($envelope = array_shift($this->publishedEnvelopes[$consumer->queue])) {
                /** @psalm-suppress TypeDoesNotContainType Value of $isCancelled will change in cancel callback */
                if ($isCancelled) {
                    array_unshift($this->publishedEnvelopes[$consumer->queue], $envelope);

                    return;
                }

                $consumer->handle($envelope);
            }
        };
        $id = EventLoop::defer($callback);
        $repeatId = EventLoop::repeat(1, $callback);

        return static function () use ($id, $repeatId, &$isCancelled): void {
            $isCancelled = true;
            EventLoop::cancel($id);
            EventLoop::cancel($repeatId);
        };
    }

    public function disconnect(): void {}

    public function setup(array $exchangeToQueues): void
    {
        foreach ($exchangeToQueues as $exchange => $queues) {
            $this->exchangeToQueues[$exchange] = array_values(
                array_unique([
                    ...($this->exchangeToQueues[$exchange] ?? []),
                    ...$queues,
                ]),
            );
        }
    }
}

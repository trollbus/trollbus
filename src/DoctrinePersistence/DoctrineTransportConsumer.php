<?php

declare(strict_types=1);

namespace Trollbus\DoctrinePersistence;

use Doctrine\DBAL\Exception;
use Revolt\EventLoop;
use Trollbus\Async\Consumer;
use Trollbus\Async\TransportConsumer;

final class DoctrineTransportConsumer implements TransportConsumer
{
    /** @var non-empty-string|null */
    private ?string $currentMessageId = null;

    public function __construct(
        private readonly DoctrineTransport $transport,
        private readonly float $interval,
    ) {}

    public function runConsume(Consumer $consumer): \Closure
    {
        EventLoop::repeat($this->interval, function () use ($consumer): void {
            $envelope = $this->transport->get($consumer->queue);

            if (null === $envelope) {
                return;
            }

            $this->currentMessageId = $messageId = $envelope->getMessageId();

            try {
                $consumer->handle($envelope);
                $this->transport->ack($messageId, $consumer->queue);
            } catch (\Throwable $e) {
                $this->transport->reject($messageId, $consumer->queue);

                throw $e;
            } finally {
                $this->currentMessageId = null;
            }
        });

        return fn() => $this->abort($consumer);
    }

    public function disconnect(): void
    {
        // Nothing ...
    }

    /**
     * @throws Exception
     */
    private function abort(Consumer $consumer): void
    {
        if (null !== $this->currentMessageId) {
            $this->transport->reject($this->currentMessageId, $consumer->queue);
        }
    }
}

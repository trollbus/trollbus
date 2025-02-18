<?php

declare(strict_types=1);

namespace Trollbus\DoctrinePersistence;

use Doctrine\DBAL\Exception;
use Trollbus\Async\TransportPublisher;
use Trollbus\MessageBus\MessageId\MessageIdNotSet;

final class DoctrineTransportPublisher implements TransportPublisher
{
    public function __construct(
        private readonly DoctrineTransport $transport,
    ) {}

    /**
     * @throws MessageIdNotSet
     * @throws Exception
     */
    #[\Override]
    public function publish(array $envelopes): void
    {
        foreach ($envelopes as $envelope) {
            $this->transport->publish($envelope);
        }
    }
}

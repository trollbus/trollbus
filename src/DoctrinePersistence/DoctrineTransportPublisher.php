<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\DoctrinePersistence;

use Doctrine\DBAL\Exception;
use Kenny1911\SisyphBus\Async\TransportPublisher;
use Kenny1911\SisyphBus\MessageBus\MessageId\Exception\MessageIdNotSet;

final class DoctrineTransportPublisher implements TransportPublisher
{
    private readonly DoctrineTransport $transport;

    public function __construct(DoctrineTransport $transport)
    {
        $this->transport = $transport;
    }

    /**
     * @throws MessageIdNotSet
     * @throws Exception
     */
    public function publish(array $envelopes): void
    {
        foreach ($envelopes as $envelope) {
            $this->transport->publish($envelope);
        }
    }
}

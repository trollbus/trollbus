<?php

declare(strict_types=1);

namespace Trollbus\DoctrinePersistence;

use Doctrine\DBAL\Exception;
use Trollbus\Async\TransportPublisher;
use Trollbus\MessageBus\MessageId\MessageIdNotSet;

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

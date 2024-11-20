<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\DoctrinePersistence;

use Kenny1911\SisyphBus\Async\TransportSetup;

final class DoctrineTransportSetup implements TransportSetup
{
    public function setup(array $exchangeToQueues): void
    {
        // Nothing ...
    }
}

<?php

declare(strict_types=1);

namespace Trollbus\DoctrinePersistence;

use Trollbus\Async\TransportSetup;

final class DoctrineTransportSetup implements TransportSetup
{
    public function setup(array $exchangeToQueues): void
    {
        // Nothing ...
    }
}

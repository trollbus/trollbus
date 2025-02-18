<?php

declare(strict_types=1);

namespace Trollbus\DoctrinePersistence;

use Trollbus\Async\TransportSetup;

final class DoctrineTransportSetup implements TransportSetup
{
    #[\Override]
    public function setup(array $exchangeToQueues): void
    {
        // Nothing ...
    }
}

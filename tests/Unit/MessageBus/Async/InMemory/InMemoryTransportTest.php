<?php

declare(strict_types=1);

namespace Trollbus\Tests\Unit\MessageBus\Async\InMemory;

use Trollbus\Tests\Unit\MessageBus\Async\TransportTestCase;

final class InMemoryTransportTest extends TransportTestCase
{
    protected function createTransport(): array
    {
        $transport = new InMemoryTransport();

        return [$transport, $transport, $transport];
    }
}

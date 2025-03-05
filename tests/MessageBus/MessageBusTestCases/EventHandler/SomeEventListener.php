<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\MessageBusTestCases\EventHandler;

final class SomeEventListener
{
    /** @var list<SomeEvent> */
    private array $listener1Events = [];

    /** @var list<SomeEvent> */
    private array $listener2Events = [];

    public function listener1(SomeEvent $event): void
    {
        $this->listener1Events[] = $event;
    }

    public function getListener1Events(): array
    {
        return $this->listener1Events;
    }

    public function listener2(SomeEvent $event): void
    {
        $this->listener2Events[] = $event;
    }

    public function getListener2Events(): array
    {
        return $this->listener2Events;
    }
}

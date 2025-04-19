<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\MessageBusTestCases\EntityFactoryHandler;

use Trollbus\Message\Event;

final class EntityWithFactoryMethodCreated implements Event
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
    ) {}
}

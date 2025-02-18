<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\MessageBusTestCases\EntityHandler;

use Trollbus\Message\Event;

final class EntityEdited implements Event
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $description,
    ) {}
}

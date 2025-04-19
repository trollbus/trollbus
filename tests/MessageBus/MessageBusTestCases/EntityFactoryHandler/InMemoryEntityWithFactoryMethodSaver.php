<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\MessageBusTestCases\EntityFactoryHandler;

use Trollbus\MessageBus\EntityHandler\EntitySaver;

final class InMemoryEntityWithFactoryMethodSaver implements EntitySaver
{
    /** @var array<EntityWithFactoryMethod> */
    public array $entities = [];

    #[\Override]
    public function save(object $entity): void
    {
        if (!$entity instanceof EntityWithFactoryMethod) {
            throw new \InvalidArgumentException('Invalid criteria.');
        }

        $this->entities[$entity->id] = $entity;
    }
}

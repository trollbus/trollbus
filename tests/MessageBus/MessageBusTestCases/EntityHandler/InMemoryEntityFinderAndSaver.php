<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\MessageBusTestCases\EntityHandler;

use Trollbus\MessageBus\EntityHandler\EntityFinder;
use Trollbus\MessageBus\EntityHandler\EntitySaver;

final class InMemoryEntityFinderAndSaver implements EntityFinder, EntitySaver
{
    /** @var array<string, Entity> */
    private array $entities = [];

    /** @var array<string, positive-int> */
    private array $saves = [];

    #[\Override]
    public function findBy(string $class, array $criteria): ?object
    {
        if (['id'] !== array_keys($criteria)) {
            throw new \InvalidArgumentException('Invalid criteria.');
        }

        if (Entity::class !== $class) {
            return null;
        }

        /** @psalm-suppress InvalidReturnStatement */
        return $this->entities[(string) $criteria['id']] ?? null;
    }

    #[\Override]
    public function save(object $entity): void
    {
        if (!$entity instanceof Entity) {
            throw new \InvalidArgumentException('Unsupported entity.');
        }

        $this->entities[$entity->getId()] = $entity;
        $this->saves[$entity->getId()] = $this->countEntitySaves($entity) + 1;
    }

    /**
     * @return non-negative-int
     */
    public function countEntitySaves(Entity $entity): int
    {
        return $this->saves[$entity->getId()] ?? 0;
    }
}

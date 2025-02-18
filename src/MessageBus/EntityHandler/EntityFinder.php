<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\EntityHandler;

interface EntityFinder
{
    /**
     * @template TEntity of object
     *
     * @param class-string<TEntity> $class
     * @param non-empty-array<non-empty-string, mixed> $criteria
     *
     * @return TEntity|null
     */
    public function findBy(string $class, array $criteria): ?object;
}

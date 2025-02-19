<?php

declare(strict_types=1);

namespace Trollbus\DoctrineORMBridge\EntityHandler;

use Doctrine\Persistence\ManagerRegistry;
use Trollbus\MessageBus\EntityHandler\EntityFinder;

final class DoctrineEntityFinder implements EntityFinder
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
    ) {}

    #[\Override]
    public function findBy(string $class, array $criteria): ?object
    {
        $em = $this->doctrine->getManagerForClass($class) ?? throw new \RuntimeException("Can not get entity manager for class {$class}.");

        return $em->getRepository($class)->findOneBy($criteria);
    }
}

<?php

declare(strict_types=1);

namespace Trollbus\DoctrineORMBridge\EntityHandler;

use Doctrine\Persistence\ManagerRegistry;
use Trollbus\MessageBus\EntityHandler\EntitySaver;

final class DoctrineEntitySaver implements EntitySaver
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly bool $flush,
    ) {}

    #[\Override]
    public function save(object $entity): void
    {
        $class = $entity::class;
        $em = $this->doctrine->getManagerForClass($entity::class) ?? throw new \RuntimeException("Can not get entity manager for class {$class}.");
        $em->persist($entity);

        if (true === $this->flush) {
            $em->flush();
        }
    }
}

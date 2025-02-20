<?php

declare(strict_types=1);

namespace Trollbus\DoctrineORMBridge\Tools;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @internal
 * @psalm-internal Trollbus\DoctrineORMBridge
 */
final class EntityManagerDescriber
{
    public static function getEntityManager(ManagerRegistry $doctrine, ?string $entityManagerName): EntityManagerInterface
    {
        $em = $doctrine->getManager($entityManagerName);

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if (!$em instanceof EntityManagerInterface) {
            throw new \LogicException(\sprintf('Unexpected object manager. Expects instance of %s.', EntityManagerInterface::class));
        }

        return $em;
    }
}

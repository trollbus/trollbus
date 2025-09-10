<?php

declare(strict_types=1);

namespace Trollbus\DoctrineORMBridge\Transaction;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Trollbus\DoctrineORMBridge\Tools\EntityManagerDescriber;
use Trollbus\MessageBus\Transaction\TransactionProvider;

final class DoctrineTransactionProvider implements TransactionProvider
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public static function fromConnectionName(ManagerRegistry $doctrine, ?string $connectionName = null): self
    {
        $connection = $doctrine->getConnection($connectionName);

        if ($connection instanceof Connection) {
            return new self($connection);
        }

        throw new \LogicException(\sprintf(
            'Invalid Connection class. Expected %s, actual %s',
            Connection::class,
            get_debug_type($connection),
        ));
    }

    public static function fromEntityManagerName(ManagerRegistry $doctrine, ?string $entityManagerName = null): self
    {
        return self::fromEntityManager(EntityManagerDescriber::getEntityManager($doctrine, $entityManagerName));
    }

    public static function fromEntityManager(EntityManagerInterface $em): self
    {
        return new self($em->getConnection());
    }

    /**
     * @throws \Throwable
     */
    #[\Override]
    public function wrapInTransaction(callable $callback): mixed
    {
        /** @psalm-suppress MixedReturnStatement In old DBAL versions {@see Connection::transactional()} was return mixed value */
        return $this->connection->transactional(static fn(): mixed => $callback());
    }
}

<?php

declare(strict_types=1);

namespace Trollbus\DoctrineORMBridge\Transaction;

use Doctrine\Persistence\ManagerRegistry;
use Trollbus\DoctrineORMBridge\Tools\EntityManagerDescriber;
use Trollbus\MessageBus\Transaction\TransactionProvider;

final class DoctrineTransactionProvider implements TransactionProvider
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly ?string $entityManagerName = null,
    ) {}

    #[\Override]
    public function wrapInTransaction(callable $callback): mixed
    {
        $em = EntityManagerDescriber::getEntityManager($this->doctrine, $this->entityManagerName);

        return $em->wrapInTransaction(static fn(): mixed => $callback());
    }
}

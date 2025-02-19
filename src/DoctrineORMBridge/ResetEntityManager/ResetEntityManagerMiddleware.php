<?php

declare(strict_types=1);

namespace Trollbus\DoctrineORMBridge\ResetEntityManager;

use Doctrine\Persistence\ManagerRegistry;
use Trollbus\DoctrineORMBridge\Tools\EntityManagerDescriber;
use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;

final class ResetEntityManagerMiddleware implements Middleware
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly ?string $entityManagerName = null,
    ) {}

    #[\Override]
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        $em = EntityManagerDescriber::getEntityManager($this->doctrine, $this->entityManagerName);

        if (!$em->isOpen()) {
            $this->doctrine->resetManager($this->entityManagerName);
        }

        return $pipeline->continue();
    }
}

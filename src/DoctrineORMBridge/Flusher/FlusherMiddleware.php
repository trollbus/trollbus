<?php

declare(strict_types=1);

namespace Trollbus\DoctrineORMBridge\Flusher;

use Doctrine\Persistence\ManagerRegistry;
use Trollbus\DoctrineORMBridge\Tools\EntityManagerDescriber;
use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;

final class FlusherMiddleware implements Middleware
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly ?string $entityManagerName = null,
    ) {}

    #[\Override]
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if ($messageContext->hasAttribute(Flushed::class)) {
            return $pipeline->continue();
        }

        $messageContext->addAttributes(new Flushed());
        $result = $pipeline->continue();
        $em = EntityManagerDescriber::getEntityManager($this->doctrine, $this->entityManagerName);
        $em->flush();

        return $result;
    }
}

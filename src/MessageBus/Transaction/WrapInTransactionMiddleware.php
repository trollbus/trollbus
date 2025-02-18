<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Transaction;

use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;

final class WrapInTransactionMiddleware implements Middleware
{
    public function __construct(
        private readonly TransactionProvider $transactionProvider,
    ) {}

    #[\Override]
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if ($messageContext->hasAttribute(InTransaction::class)) {
            return $pipeline->continue();
        }

        $messageContext->addAttributes(new InTransaction());

        return $this->transactionProvider->wrapInTransaction(static fn(): mixed => $pipeline->continue());
    }
}

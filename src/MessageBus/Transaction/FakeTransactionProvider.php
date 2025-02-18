<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Transaction;

final class FakeTransactionProvider implements TransactionProvider
{
    #[\Override]
    public function wrapInTransaction(callable $callback): mixed
    {
        return $callback();
    }
}

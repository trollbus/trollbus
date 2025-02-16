<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Transaction;

interface TransactionProvider
{
    /**
     * @template TReturn
     *
     * @param callable(): TReturn $callback
     *
     * @return TReturn
     */
    public function wrapInTransaction(callable $callback): mixed;
}

<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\Async;

final class MessageClassBasedExchangeResolver implements ExchangeResolver
{
    public function resolve(string $messageClass): string
    {
        /** @var non-empty-string */
        return str_replace('\\', '.', $messageClass);
    }
}

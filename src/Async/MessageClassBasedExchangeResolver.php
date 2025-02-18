<?php

declare(strict_types=1);

namespace Trollbus\Async;

final class MessageClassBasedExchangeResolver implements ExchangeResolver
{
    #[\Override]
    public function resolve(string $messageClass): string
    {
        /** @var non-empty-string */
        return str_replace('\\', '.', $messageClass);
    }
}

<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\Async;

use Kenny1911\SisyphBus\Message\Message;

interface ExchangeResolver
{
    /**
     * @param class-string<Message> $messageClass
     * @return non-empty-string
     */
    public function resolve(string $messageClass): string;
}

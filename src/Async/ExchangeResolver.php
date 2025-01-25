<?php

declare(strict_types=1);

namespace Trollbus\Async;

use Trollbus\Message\Message;

interface ExchangeResolver
{
    /**
     * @param class-string<Message> $messageClass
     * @return non-empty-string
     */
    public function resolve(string $messageClass): string;
}

<?php

declare(strict_types=1);

namespace Trollbus\PgmqTransport;

/**
 * @see https://github.com/thesis-php/pgmq/blob/0.1.x/src/SendMessage.php
 */
final class PgmqSendMessage
{
    /**
     * @param non-empty-string $valueJson
     * @param non-empty-string|null $headerJson
     */
    public function __construct(
        public string $valueJson,
        public ?string $headerJson = null,
    ) {}
}

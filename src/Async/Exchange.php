<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\Async;

use Kenny1911\SisyphBus\MessageBus\Stamp;

final class Exchange implements Stamp
{
    /** @var non-empty-string */
    public readonly string $exchange;

    /**
     * @param non-empty-string $exchange
     */
    public function __construct(string $exchange)
    {
        $this->exchange = $exchange;
    }
}

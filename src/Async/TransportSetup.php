<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\Async;

interface TransportSetup
{
    /**
     * @param array<non-empty-string, list<non-empty-string>> $exchangeToQueues
     */
    public function setup(array $exchangeToQueues): void;
}

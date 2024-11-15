<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\Async;

interface TransportConsumer
{
    /**
     * @return \Closure(): void the cancel function
     */
    public function runConsume(Consumer $consumer): \Closure;

    public function disconnect(): void;
}

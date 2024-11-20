<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\DoctrinePersistence;

interface ConsumeIdGenerator
{
    /**
     * @return non-empty-string
     */
    public function generate(): string;
}

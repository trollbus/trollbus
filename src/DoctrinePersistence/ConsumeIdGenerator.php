<?php

declare(strict_types=1);

namespace Trollbus\DoctrinePersistence;

interface ConsumeIdGenerator
{
    /**
     * @return non-empty-string
     */
    public function generate(): string;
}

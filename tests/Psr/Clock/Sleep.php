<?php

declare(strict_types=1);

namespace Trollbus\Tests\Psr\Clock;

interface Sleep
{
    public function sleep(int $seconds): void;
}

<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Async\Retry;

use Trollbus\MessageBus\Stamp;

final class Retry implements Stamp
{
    /** @var positive-int */
    public readonly int $count;

    /**
     * @param non-empty-list<non-negative-int> $timeouts
     */
    public function __construct(
        public readonly array $timeouts,
    ) {
        $this->count = \count($this->timeouts);
    }

    /**
     * @param non-negative-int $timeout
     * @param non-negative-int $count
     */
    public static function linear(int $timeout, int $count = 3): self
    {
        return new self(array_fill(0, $count, $timeout));
    }

    /**
     * @param non-negative-int $initTimeout
     * @param non-negative-int $count
     * @param int<2, max> $multiplier
     */
    public static function exponent(int $initTimeout, int $count, int $multiplier): self
    {
        $timeouts = [];

        for ($i = 0; $i < $count; ++$i) {
            $timeouts[] = $initTimeout * ($multiplier ** $i);
        }

        return new self($timeouts);
    }
}

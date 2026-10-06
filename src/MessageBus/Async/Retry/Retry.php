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
        $timeouts = array_fill(0, $count, $timeout);
        \assert([] !== $timeouts);

        return new self($timeouts);
    }

    /**
     * @param non-negative-int $initTimeout
     * @param non-negative-int $count
     * @param int<2, max> $multiplier
     */
    public static function exponent(int $initTimeout, int $count, int $multiplier): self
    {
        /** @var list<non-negative-int> $timeouts */
        $timeouts = [];

        for ($i = 0; $i < $count; ++$i) {
            $timeout = $initTimeout * (int) ($multiplier ** $i);
            \assert($timeout >= 0);
            $timeouts[] = $timeout;
        }

        \assert([] !== $timeouts);

        return new self($timeouts);
    }
}

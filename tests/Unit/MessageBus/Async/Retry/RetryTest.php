<?php

declare(strict_types=1);

namespace Trollbus\Tests\Unit\MessageBus\Async\Retry;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Trollbus\MessageBus\Async\Retry\Retry;

final class RetryTest extends TestCase
{
    /**
     * @param non-negative-int $timeout
     * @param non-negative-int $count
     */
    #[TestWith(['expectedTimeouts' => [5, 5, 5, 5], 'expectedCount' => 4, 'timeout' => 5, 'count' => 4])]
    #[TestWith(['expectedTimeouts' => [3], 'expectedCount' => 1, 'timeout' => 3, 'count' => 1])]
    public function testLinear(array $expectedTimeouts, int $expectedCount, int $timeout, int $count): void
    {
        $retry = Retry::linear(timeout: $timeout, count: $count);

        self::assertSame($expectedTimeouts, $retry->timeouts);
        self::assertSame($expectedCount, $retry->count);
    }

    /**
     * @param non-negative-int $initTimeout
     * @param non-negative-int $count
     * @param int<2, max> $multiplier
     */
    #[TestWith(['expectedTimeouts' => [1, 2, 4, 8, 16], 'expectedCount' => 5, 'initTimeout' => 1, 'count' => 5, 'multiplier' => 2])]
    #[TestWith(['expectedTimeouts' => [2, 6, 18], 'expectedCount' => 3, 'initTimeout' => 2, 'count' => 3, 'multiplier' => 3])]
    public function testExponent(array $expectedTimeouts, int $expectedCount, int $initTimeout, int $count, int $multiplier): void
    {
        $retry = Retry::exponent(initTimeout: $initTimeout, count: $count, multiplier: $multiplier);

        self::assertSame($expectedTimeouts, $retry->timeouts);
        self::assertSame($expectedCount, $retry->count);
    }
}

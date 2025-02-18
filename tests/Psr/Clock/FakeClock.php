<?php

declare(strict_types=1);

namespace Trollbus\Tests\Psr\Clock;

use Psr\Clock\ClockInterface;

final class FakeClock implements ClockInterface, Sleep
{
    private int $timestamp;

    public function __construct(
        \DateTimeInterface|int $timestamp,
    ) {
        $this->timestamp = $timestamp instanceof \DateTimeInterface ? $timestamp->getTimestamp() : $timestamp;
    }

    #[\Override]
    public function sleep(int $seconds): void
    {
        $this->timestamp += $seconds;
    }

    #[\Override]
    public function now(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat('U', (string) $this->timestamp) ?: throw new \RuntimeException();
    }
}

<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\Async\Delay;

use Kenny1911\SisyphBus\MessageBus\Stamp;

final class Delay implements Stamp
{
    private const SECONDS_MULTIPLIER = 1000;
    private const MINUTES_MULTIPLIER = self::SECONDS_MULTIPLIER * 60;
    private const HOURS_MULTIPLIER = self::MINUTES_MULTIPLIER * 60;
    private const DAYS_MULTIPLIER = self::HOURS_MULTIPLIER * 24;

    /** @var positive-int */
    public readonly int $milliseconds;

    /**
     * @param positive-int $milliseconds
     */
    public function __construct(int $milliseconds)
    {
        $this->milliseconds = $milliseconds;
    }

    public static function till(\DateTimeImmutable $time, \DateTimeImmutable $now = new \DateTimeImmutable()): self
    {
        $milliseconds = (int) $time->format('Uv') - (int) $now->format('Uv');

        if ($milliseconds > 0) {
            return new self($milliseconds);
        }

        throw new \InvalidArgumentException('Invalid time.');
    }

    /**
     * @param positive-int $seconds
     */
    public static function fromSeconds(int $seconds): self
    {
        return new self($seconds * self::SECONDS_MULTIPLIER);
    }

    /**
     * @param positive-int $minutes
     */
    public static function fromMinutes(int $minutes): self
    {
        return self::fromSeconds($minutes * self::MINUTES_MULTIPLIER);
    }

    /**
     * @param positive-int $hours
     */
    public static function fromHours(int $hours): self
    {
        return self::fromMinutes($hours * self::HOURS_MULTIPLIER);
    }

    /**
     * @param positive-int $days
     */
    public static function fromDays(int $days): self
    {
        return self::fromHours($days * self::DAYS_MULTIPLIER);
    }
}

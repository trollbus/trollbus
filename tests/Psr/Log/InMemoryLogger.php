<?php

declare(strict_types=1);

namespace Trollbus\Tests\Psr\Log;

use Psr\Log\AbstractLogger;

final class InMemoryLogger extends AbstractLogger
{
    /** @var list<array{string, string, array}> */
    private array $logs = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->logs[] = [(string) $level, (string) $message, $context];
    }

    /**
     * @return list<array{string, string, array}>
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    public function reset(): void
    {
        $this->logs = [];
    }
}

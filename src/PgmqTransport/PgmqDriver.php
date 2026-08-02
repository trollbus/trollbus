<?php

declare(strict_types=1);

namespace Trollbus\PgmqTransport;

interface PgmqDriver
{
    /**
     * @param non-empty-string $queue
     */
    public function createQueue(string $queue): void;

    /**
     * @param non-empty-string $queue
     */
    public function dropQueue(string $queue): void;

    /**
     * @param non-empty-string $queue
     * @param \Closure(PgmqMessage): void $callback
     *
     * @return \Closure(): void the cancel function
     */
    public function consume(string $queue, \Closure $callback): \Closure;

    /**
     * @param non-empty-string $pattern
     * @param non-empty-string $queue
     */
    public function bindTopic(string $pattern, string $queue): void;

    /**
     * @param non-empty-string $pattern
     * @param non-empty-string $queue
     */
    public function unbindTopic(string $pattern, string $queue): void;

    /**
     * @param non-empty-string $pattern
     * @param non-empty-string $message
     * @param non-empty-string|null $headers
     * @param non-negative-int $delay
     */
    public function sendTopic(string $pattern, string $message, ?string $headers = null, int $delay = 0): void;

    public function disconnect(): void;
}

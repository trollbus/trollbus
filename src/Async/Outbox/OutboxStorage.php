<?php

declare(strict_types=1);

namespace Trollbus\Async\Outbox;

interface OutboxStorage
{
    /**
     * @param non-empty-string|null $queue
     * @param non-empty-string $messageId
     */
    public function find(?string $queue, string $messageId): ?Outbox;

    /**
     * @param non-empty-string|null $queue
     * @param non-empty-string $messageId
     * @throws OutboxAlreadyExists
     */
    public function create(?string $queue, string $messageId, Outbox $outbox): void;

    /**
     * @param non-empty-string|null $queue
     * @param non-empty-string $messageId
     */
    public function empty(?string $queue, string $messageId): void;
}

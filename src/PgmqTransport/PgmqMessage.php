<?php

declare(strict_types=1);

namespace Trollbus\PgmqTransport;

/**
 * @psalm-type RawMessage = array{
 *     msg_id: int,
 *     read_ct: non-negative-int,
 *     enqueued_at: non-empty-string,
 *     vt: non-empty-string,
 *     message: non-empty-string,
 *     headers: non-empty-string|null,
 * }
 *
 * @see https://github.com/thesis-php/pgmq/blob/0.1.x/src/Message.php
 */
final class PgmqMessage
{
    /**
     * @param non-negative-int $readCount
     * @param non-empty-string $value
     * @param non-empty-string|null $headers
     */
    public function __construct(
        public int $id,
        public int $readCount,
        public \DateTimeImmutable $enqueuedAt,
        public string $value,
        public \DateTimeImmutable $visibilityTimeout,
        public ?string $headers = null,
    ) {}

    public static function fromArray(array $row): self
    {
        /** @var RawMessage $row */
        return new self(
            id: $row['msg_id'],
            readCount: $row['read_ct'],
            enqueuedAt: new \DateTimeImmutable($row['enqueued_at']),
            value: $row['message'],
            visibilityTimeout: new \DateTimeImmutable($row['vt']),
            headers: $row['headers'],
        );
    }
}

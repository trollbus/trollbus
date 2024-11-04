<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\MessageId;

final class RandomMessageIdGenerator implements MessageIdGenerator
{
    /** @var positive-int */
    private readonly int $bytes;

    /**
     * @param positive-int $bytes
     */
    public function __construct(int $bytes = 16)
    {
        $this->bytes = $bytes;
    }

    public function generate(): string
    {
        return bin2hex(random_bytes($this->bytes));
    }
}

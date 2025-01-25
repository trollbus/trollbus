<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\MessageId;

use Trollbus\MessageBus\Stamp;

final class MessageId implements Stamp
{
    /**
     * @param non-empty-string $messageId
     */
    public function __construct(
        public readonly string $messageId,
    ) {}
}

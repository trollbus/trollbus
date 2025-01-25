<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\MessageId;

use Trollbus\MessageBus\Stamp;

final class MessageId implements Stamp
{
    /** @var non-empty-string */
    public readonly string $messageId;

    /**
     * @param non-empty-string $messageId
     */
    public function __construct(string $messageId)
    {
        $this->messageId = $messageId;
    }
}

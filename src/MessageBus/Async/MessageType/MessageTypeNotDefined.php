<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Async\MessageType;

final class MessageTypeNotDefined extends \Exception
{
    public static function create(): self
    {
        return new self('Message type not defined.');
    }
}

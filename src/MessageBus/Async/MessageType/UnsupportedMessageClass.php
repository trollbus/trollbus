<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Async\MessageType;

use Trollbus\Message\Message;

final class UnsupportedMessageClass extends \Exception
{
    /**
     * @param class-string<Message> $class
     */
    public static function create(string $class): self
    {
        return new self("Can not resolve message type for class \"{$class}\".");
    }
}

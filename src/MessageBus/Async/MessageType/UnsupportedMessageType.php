<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Async\MessageType;

final class UnsupportedMessageType extends \Exception
{
    /**
     * @param non-empty-string $type
     */
    public static function create(string $type): self
    {
        return new self("Can not resolve message class for type \"{$type}\".");
    }
}

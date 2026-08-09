<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Async\MessageType;

use Trollbus\Message\Message;

final class OrigClassMessageTypeResolver implements MessageTypeResolver
{
    public function resolveType(string $class): string
    {
        // Normalize class name
        return (new \ReflectionClass($class))->getName();
    }

    public function resolveClass(string $type): string
    {
        if (!(class_exists($type) && is_subclass_of($type, Message::class, true))) {
            /** @psalm-suppress ArgumentTypeCoercion type of $type variable is always non-empty-string */
            throw UnsupportedMessageType::create($type);
        }

        // Normalize class name
        return (new \ReflectionClass($type))->getName();
    }
}

<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Async\MessageType;

use Trollbus\Message\Message;

interface MessageTypeResolver
{
    /**
     * @param class-string<Message> $class
     *
     * @return non-empty-string
     *
     * @throws UnsupportedMessageClass
     */
    public function resolveType(string $class): string;

    /**
     * @param non-empty-string $type
     *
     * @return class-string<Message>
     *
     * @throws UnsupportedMessageType
     */
    public function resolveClass(string $type): string;
}

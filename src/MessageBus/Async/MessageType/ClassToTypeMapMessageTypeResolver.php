<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Async\MessageType;

use Trollbus\Message\Message;

final class ClassToTypeMapMessageTypeResolver implements MessageTypeResolver
{
    /**
     * @param array<class-string<Message>, non-empty-string> $classToTypeMap
     */
    public function __construct(
        private array $classToTypeMap,
    ) {
        $diff = array_diff_assoc($this->classToTypeMap, array_unique($this->classToTypeMap));

        if ([] !== $diff) {
            throw new \LogicException(\sprintf('Duplicate message types: %s.', implode(', ', $diff)));
        }
    }

    public function resolveType(string $class): string
    {
        return $this->classToTypeMap[$class] ?? throw UnsupportedMessageClass::create($class);
    }

    public function resolveClass(string $type): string
    {
        $class = array_search($type, $this->classToTypeMap, true);

        if (false === $class) {
            throw UnsupportedMessageType::create($type);
        }

        return $class;
    }
}

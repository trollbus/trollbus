<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Async\MessageType;

final class ChainMessageTypeResolver implements MessageTypeResolver
{
    /**
     * @param iterable<MessageTypeResolver> $resolvers
     */
    public function __construct(
        private readonly iterable $resolvers,
    ) {}

    public function resolveType(string $class): string
    {
        foreach ($this->resolvers as $resolver) {
            try {
                return $resolver->resolveType($class);
            } catch (UnsupportedMessageClass) {
            }
        }

        throw UnsupportedMessageClass::create($class);
    }

    public function resolveClass(string $type): string
    {
        foreach ($this->resolvers as $resolver) {
            try {
                return $resolver->resolveClass($type);
            } catch (UnsupportedMessageType) {
            }
        }

        throw UnsupportedMessageType::create($type);
    }
}

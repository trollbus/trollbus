<?php

declare(strict_types=1);

namespace Trollbus\Tests\Unit\MessageBus\Async\MessageType;

use PHPUnit\Framework\TestCase;
use Trollbus\MessageBus\Async\MessageType\ClassToTypeMapMessageTypeResolver;
use Trollbus\MessageBus\Async\MessageType\UnsupportedMessageClass;
use Trollbus\MessageBus\Async\MessageType\UnsupportedMessageType;

final class ClassToTypeMapMessageTypeResolverTest extends TestCase
{
    public function testConstructorThrowsLogicExceptionOnDuplicateType(): void
    {
        self::expectException(\LogicException::class);
        self::expectExceptionMessage('Duplicate message types: foo.');

        new ClassToTypeMapMessageTypeResolver([FooMessage::class => 'foo', BarMessage::class => 'foo']);
    }

    public function testResolveTypeReturnsTypeFromMap(): void
    {
        $resolver = new ClassToTypeMapMessageTypeResolver([FooMessage::class => 'foo']);

        self::assertSame('foo', $resolver->resolveType(FooMessage::class));
    }

    public function testResolveTypeThrowsOnUnknownClass(): void
    {
        self::expectException(UnsupportedMessageClass::class);
        self::expectExceptionMessage(\sprintf('Can not resolve message type for class "%s".', FooMessage::class));

        $resolver = new ClassToTypeMapMessageTypeResolver([]);
        $resolver->resolveType(FooMessage::class);
    }

    public function testResolveClassReturnsClassFromType(): void
    {
        $resolver = new ClassToTypeMapMessageTypeResolver([FooMessage::class => 'foo']);

        self::assertSame(FooMessage::class, $resolver->resolveClass('foo'));
    }

    public function testResolveClassThrowsOnUnknownType(): void
    {
        self::expectException(UnsupportedMessageType::class);
        self::expectExceptionMessage('Can not resolve message class for type "unknown-type".');

        $resolver = new ClassToTypeMapMessageTypeResolver([]);
        $resolver->resolveClass('unknown-type');
    }
}

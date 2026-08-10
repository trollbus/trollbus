<?php

declare(strict_types=1);

namespace Trollbus\Tests\Unit\MessageBus\Async\MessageType;

use PHPUnit\Framework\TestCase;
use Trollbus\MessageBus\Async\MessageType\ChainMessageTypeResolver;
use Trollbus\MessageBus\Async\MessageType\ClassToTypeMapMessageTypeResolver;
use Trollbus\MessageBus\Async\MessageType\OrigClassMessageTypeResolver;
use Trollbus\MessageBus\Async\MessageType\UnsupportedMessageClass;
use Trollbus\MessageBus\Async\MessageType\UnsupportedMessageType;

final class ChainMessageTypeResolverTest extends TestCase
{
    public function testResolveTypeUsesFirstSupportingResolver(): void
    {
        $resolver = new ChainMessageTypeResolver([
            new ClassToTypeMapMessageTypeResolver([FooMessage::class => 'foo']),
            new OrigClassMessageTypeResolver(),
        ]);

        self::assertSame('foo', $resolver->resolveType(FooMessage::class));
        self::assertSame(BarMessage::class, $resolver->resolveType(BarMessage::class));
    }

    public function testResolveTypeThrowsWhenNoResolverSupportsClass(): void
    {
        $this->expectException(UnsupportedMessageClass::class);
        $this->expectExceptionMessage(\sprintf('Can not resolve message type for class "%s".', FooMessage::class));

        $resolver = new ChainMessageTypeResolver([
            new ClassToTypeMapMessageTypeResolver([]),
        ]);
        $resolver->resolveType(FooMessage::class);
    }

    public function testResolveClassUsesFirstSupportingResolver(): void
    {
        $resolver = new ChainMessageTypeResolver([
            new ClassToTypeMapMessageTypeResolver([FooMessage::class => 'foo']),
            new OrigClassMessageTypeResolver(),
        ]);

        self::assertSame(FooMessage::class, $resolver->resolveClass('foo'));
        self::assertSame(FooMessage::class, $resolver->resolveClass(FooMessage::class));
        self::assertSame(BarMessage::class, $resolver->resolveClass(BarMessage::class));
    }

    public function testResolveClassThrowsWhenNoResolverSupportsType(): void
    {
        self::expectException(UnsupportedMessageType::class);
        self::expectExceptionMessage('Can not resolve message class for type "resolved-type".');

        $resolver = new ChainMessageTypeResolver([
            new ClassToTypeMapMessageTypeResolver([]),
        ]);
        $resolver->resolveClass('resolved-type');
    }
}

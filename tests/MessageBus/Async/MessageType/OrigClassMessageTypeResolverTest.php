<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\Async\MessageType;

use PHPUnit\Framework\TestCase;
use Trollbus\MessageBus\Async\MessageType\OrigClassMessageTypeResolver;
use Trollbus\MessageBus\Async\MessageType\UnsupportedMessageType;

final class OrigClassMessageTypeResolverTest extends TestCase
{
    public function testResolveTypeReturnsNormalizedClassName(): void
    {
        $resolver = new OrigClassMessageTypeResolver();

        self::assertSame(FooMessage::class, $resolver->resolveType(FooMessage::class));
    }

    public function testResolveClassReturnsNormalizedClassForMessage(): void
    {
        $resolver = new OrigClassMessageTypeResolver();

        self::assertSame(FooMessage::class, $resolver->resolveClass(FooMessage::class));
    }

    public function testResolveClassThrowsOnUnknownClass(): void
    {
        self::expectException(UnsupportedMessageType::class);

        $resolver = new OrigClassMessageTypeResolver();
        $resolver->resolveClass('Unknown\Class\That\Does\Not\Exist');
    }

    public function testResolveClassThrowsWhenClassIsNotAMessage(): void
    {
        self::expectException(UnsupportedMessageType::class);

        $resolver = new OrigClassMessageTypeResolver();
        $resolver->resolveClass(\stdClass::class);
    }
}

<?php

declare(strict_types=1);

namespace Trollbus\Tests\Unit\MessageBus\Async\ObjectNormalizer;

use PHPUnit\Framework\TestCase;
use Trollbus\MessageBus\Async\ObjectNormalizer\DenormalizationError;
use Trollbus\MessageBus\Async\ObjectNormalizer\PhpNativeSerializer;

final class PhpNativeSerializerTest extends TestCase
{
    public function testNormalizeAndDenormalizeRoundTrip(): void
    {
        $serializer = new PhpNativeSerializer();

        $object = new SomeMessage('value');

        $data = $serializer->normalize($object);

        self::assertIsString($data);

        $deserializedObject = $serializer->denormalize($data, SomeMessage::class);

        self::assertInstanceOf(SomeMessage::class, $deserializedObject);
        self::assertEquals($object, $deserializedObject);
    }

    public function testDenormalizeThrowsWhenDataIsNotString(): void
    {
        self::expectException(DenormalizationError::class);
        self::expectExceptionMessage('Can not denormalize data to object instance "DateTimeImmutable".');

        $serializer = new PhpNativeSerializer();
        $serializer->denormalize(123, \DateTimeImmutable::class);
    }

    public function testDenormalizeThrowsWhenInstanceDoesNotMatchClass(): void
    {
        self::expectException(DenormalizationError::class);
        self::expectExceptionMessage('Can not denormalize data to object instance "DateTimeImmutable".');

        $serializer = new PhpNativeSerializer();
        $data = $serializer->normalize(new \stdClass());

        $serializer->denormalize($data, \DateTimeImmutable::class);
    }
}

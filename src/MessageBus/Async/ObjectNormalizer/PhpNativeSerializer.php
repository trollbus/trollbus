<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Async\ObjectNormalizer;

final class PhpNativeSerializer implements ObjectNormalizer, ObjectDenormalizer
{
    public function normalize(object $object): mixed
    {
        return serialize($object);
    }

    public function denormalize(mixed $data, string $class): object
    {
        if (\is_string($data)) {
            $object = unserialize($data);

            if ($object instanceof $class) {
                return $object;
            }
        }

        throw DenormalizationError::create($class);
    }
}

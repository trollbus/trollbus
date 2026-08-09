<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Async\ObjectNormalizer;

interface ObjectDenormalizer
{
    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     *
     * @throws DenormalizationError
     */
    public function denormalize(mixed $data, string $class): object;
}

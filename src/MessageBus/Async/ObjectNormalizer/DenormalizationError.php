<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Async\ObjectNormalizer;

final class DenormalizationError extends \Exception
{
    /**
     * @param class-string $class
     */
    public static function create(string $class): self
    {
        return new self("Can not denormalize data to object instance \"{$class}\".");
    }
}

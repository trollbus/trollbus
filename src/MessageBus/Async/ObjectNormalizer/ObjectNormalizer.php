<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Async\ObjectNormalizer;

interface ObjectNormalizer
{
    public function normalize(object $object): mixed;
}

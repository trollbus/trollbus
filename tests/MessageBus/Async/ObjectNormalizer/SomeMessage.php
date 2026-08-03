<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\Async\ObjectNormalizer;

use Trollbus\Message\Message;

/**
 * @implements Message<void>
 */
final class SomeMessage implements Message
{
    public function __construct(
        public string $value,
    ) {}
}

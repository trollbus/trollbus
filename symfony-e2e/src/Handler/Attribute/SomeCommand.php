<?php

declare(strict_types=1);

namespace App\Handler\Attribute;

use Trollbus\Message\Message;

/**
 * @implements Message<true>
 */
final readonly class SomeCommand implements Message
{
    public function __construct(
        public string $arg,
    ) {}
}

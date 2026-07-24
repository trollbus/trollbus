<?php

declare(strict_types=1);

namespace App\Middleware\Attribute;

use Trollbus\Message\Message;

/**
 * @implements Message<void>
 */
final readonly class SomeCommand implements Message {}

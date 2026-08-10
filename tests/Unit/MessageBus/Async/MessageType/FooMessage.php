<?php

declare(strict_types=1);

namespace Trollbus\Tests\Unit\MessageBus\Async\MessageType;

use Trollbus\Message\Message;

/**
 * @implements Message<void>
 */
final class FooMessage implements Message
{
    public function __construct() {}
}

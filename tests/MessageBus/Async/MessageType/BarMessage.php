<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\Async\MessageType;

use Trollbus\Message\Message;

/**
 * @implements Message<void>
 */
final class BarMessage implements Message
{
    public function __construct() {}
}

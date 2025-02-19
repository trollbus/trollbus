<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\MessageBusTestCases\NoHandler;

use Trollbus\Message\Message;

/**
 * @implements Message<void>
 */
final class NoHandlerMessage implements Message {}

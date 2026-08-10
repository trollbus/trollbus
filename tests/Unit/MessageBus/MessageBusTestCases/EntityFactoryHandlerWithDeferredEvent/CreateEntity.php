<?php

declare(strict_types=1);

namespace Trollbus\Tests\Unit\MessageBus\MessageBusTestCases\EntityFactoryHandlerWithDeferredEvent;

use Trollbus\Message\Message;

/**
 * @implements Message<void>
 */
final class CreateEntity implements Message {}

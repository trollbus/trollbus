<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\MessageBusTestCases\NestedDispatch;

use Trollbus\Message\Message;

/**
 * @implements Message<null>
 */
final class NestedDispatchLevel2 implements Message {}

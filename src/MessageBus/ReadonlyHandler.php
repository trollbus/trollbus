<?php

declare(strict_types=1);

namespace Trollbus\MessageBus;

use Trollbus\Message\Message;

/**
 * @template-covariant TResult
 * @template-covariant TMessage of Message<TResult>
 * @internal
 * @psalm-internal Trollbus\MessageBus
 */
interface ReadonlyHandler {}

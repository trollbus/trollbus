<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus;

use Kenny1911\SisyphBus\Message\Message;

/**
 * @template-covariant TResult
 * @template-covariant TMessage of Message<TResult>
 * @internal
 * @psalm-internal Kenny1911\SisyphBus\MessageBus
 */
interface ReadonlyHandler {}

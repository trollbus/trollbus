<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\Async;

use Kenny1911\SisyphBus\Message\Message;
use Kenny1911\SisyphBus\MessageBus\Envelop;

interface TransportPublisher
{
    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param non-empty-list<Envelop<TResult, TMessage>> $envelopes
     */
    public function publish(array $envelopes): void;
}

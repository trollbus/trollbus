<?php

declare(strict_types=1);

namespace Trollbus\Async;

use Trollbus\Message\Message;
use Trollbus\MessageBus\Envelope;

interface TransportPublisher
{
    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param non-empty-list<Envelope<TResult, TMessage>> $envelopes
     */
    public function publish(array $envelopes): void;
}

<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\Handler;

use Kenny1911\SisyphBus\Message\Event;
use Kenny1911\SisyphBus\Message\Message;
use Kenny1911\SisyphBus\MessageBus\Envelop;
use Kenny1911\SisyphBus\MessageBus\Handler;
use Kenny1911\SisyphBus\MessageBus\MessageContext;

/**
 * @template TMessage of Event
 * @implements Handler<void, TMessage>
 */
final class EventHandler implements Handler
{
    /** @var iterable<Handler<void, TMessage>> */
    private readonly iterable $handlers;

    /**
     * @param iterable<Handler<void, TMessage>> $handlers
     */
    public function __construct(iterable $handlers)
    {
        $this->handlers = $handlers;
    }

    public function handle(MessageContext $messageContext): mixed
    {
        foreach ($this->handlers as $handler) {
            $handler->handle($messageContext);
        }

        return null;
    }
}

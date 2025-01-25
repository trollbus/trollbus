<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Handler;

use Trollbus\Message\Event;
use Trollbus\MessageBus\Handler;
use Trollbus\MessageBus\MessageContext;

/**
 * @template TMessage of Event
 * @implements Handler<void, TMessage>
 */
final class EventHandler implements Handler
{
    /**
     * @param iterable<Handler<void, TMessage>> $handlers
     */
    public function __construct(private readonly iterable $handlers) {}

    /**
     * @throws \JsonException
     */
    public function id(): string
    {
        $ids = [];

        foreach ($this->handlers as $handler) {
            $ids[] = $handler->id();
        }

        sort($ids);

        return json_encode($ids, JSON_THROW_ON_ERROR);
    }

    public function handle(MessageContext $messageContext): mixed
    {
        foreach ($this->handlers as $handler) {
            $handler->handle($messageContext);
        }

        return null;
    }
}

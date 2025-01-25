<?php

declare(strict_types=1);

namespace Trollbus\Async;

use Trollbus\Message\Message;
use Trollbus\MessageBus\Handler;
use Trollbus\MessageBus\MessageContext;

/**
 * @template TMessage of Message<void>
 * @implements Handler<void, TMessage>
 */
final class Publisher implements Handler
{
    /** @var non-empty-string */
    private readonly string $id;

    private readonly TransportPublisher $publisher;

    /**
     * @param non-empty-string $id
     */
    public function __construct(string $id, TransportPublisher $publisher)
    {
        $this->id = $id;
        $this->publisher = $publisher;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function handle(MessageContext $messageContext): mixed
    {
        $this->publisher->publish([$messageContext->envelop]);

        return null;
    }
}

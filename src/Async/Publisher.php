<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\Async;

use Kenny1911\SisyphBus\Message\Message;
use Kenny1911\SisyphBus\MessageBus\Handler;
use Kenny1911\SisyphBus\MessageBus\MessageContext;

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

<?php

declare(strict_types=1);

namespace Trollbus\Async;

use Trollbus\Message\Message;
use Trollbus\MessageBus\Handler;
use Trollbus\MessageBus\MessageContext;

/**
 * @template TMessage of Message<null>
 * @implements Handler<null, TMessage>
 */
final class Publisher implements Handler
{
    /**
     * @param non-empty-string $id
     */
    public function __construct(
        private readonly string $id,
        private readonly TransportPublisher $publisher,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function handle(MessageContext $messageContext): mixed
    {
        $this->publisher->publish([$messageContext->getEnvelop()]);

        return null;
    }
}

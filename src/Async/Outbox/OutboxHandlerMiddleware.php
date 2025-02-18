<?php

declare(strict_types=1);

namespace Trollbus\Async\Outbox;

use Trollbus\Async\TransportPublisher;
use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;
use Trollbus\MessageBus\Transaction\TransactionProvider;

final class OutboxHandlerMiddleware implements Middleware
{
    public function __construct(
        private readonly OutboxStorage $outboxStorage,
        private readonly TransactionProvider $transactionProvider,
        private readonly TransportPublisher $transportPublisher,
    ) {}

    #[\Override]
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if ($messageContext->hasAttribute(Outbox::class)) {
            return $pipeline->continue();
        }

        return $this->transactionProvider->wrapInTransaction(function () use ($messageContext, $pipeline): mixed {
            $messageId = $messageContext->getMessageId();
            $outbox = new Outbox();
            $result = $pipeline->continue();

            $envelopes = $outbox->getEnvelopes();

            if ([] !== $envelopes) {
                // Optimize, 1 query for completed Outbox instead 2
                // $this->outboxStorage->create(null, $messageId, $outbox);
                // $this->outboxStorage->empty(null, $messageId);
                $this->outboxStorage->create(null, $messageId, new Outbox());
                $this->transportPublisher->publish($envelopes);
            }

            return $result;
        });
    }
}

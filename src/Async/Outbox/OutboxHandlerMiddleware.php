<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\Async\Outbox;

use Kenny1911\SisyphBus\Async\TransportPublisher;
use Kenny1911\SisyphBus\MessageBus\MessageContext;
use Kenny1911\SisyphBus\MessageBus\Middleware\Middleware;
use Kenny1911\SisyphBus\MessageBus\Middleware\Pipeline;
use Kenny1911\SisyphBus\MessageBus\Transaction\TransactionProvider;

final class OutboxHandlerMiddleware implements Middleware
{
    private readonly OutboxStorage $outboxStorage;

    private readonly TransactionProvider $transactionProvider;

    private readonly TransportPublisher $transportPublisher;

    public function __construct(
        OutboxStorage $outboxStorage,
        TransactionProvider $transactionProvider,
        TransportPublisher $transportPublisher,
    ) {
        $this->transactionProvider = $transactionProvider;
        $this->outboxStorage = $outboxStorage;
        $this->transportPublisher = $transportPublisher;
    }

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

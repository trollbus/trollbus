<?php

declare(strict_types=1);

namespace Trollbus\Async\Outbox;

use Trollbus\Async\Queue;
use Trollbus\Async\TransportPublisher;
use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\MessageId\MessageIdNotSet;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;
use Trollbus\MessageBus\Transaction\TransactionProvider;

final class OutboxConsumerMiddleware implements Middleware
{
    private readonly OutboxStorage $outboxStorage;

    private readonly TransactionProvider $transactionProvider;

    private readonly TransportPublisher $transportPublisher;

    public function __construct(
        OutboxStorage $outboxStorage,
        TransactionProvider $transactionProvider,
        TransportPublisher $transportPublisher,
    ) {
        $this->outboxStorage = $outboxStorage;
        $this->transactionProvider = $transactionProvider;
        $this->transportPublisher = $transportPublisher;
    }

    /**
     * @throws MessageIdNotSet
     */
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if (!$messageContext->hasAttribute(Outbox::class)) {
            return $pipeline->continue();
        }

        $queue = $messageContext->getAttribute(Queue::class)?->queue ?? throw new \LogicException('Queue not set.');
        $messageId = $messageContext->getMessageId();

        $outbox = $this->outboxStorage->find($queue, $messageId);

        if (null === $outbox) {
            $outbox = new Outbox();
            $messageContext->addAttributes($outbox);

            $this->transactionProvider->wrapInTransaction(function () use ($pipeline, $queue, $messageId, $outbox): void {
                $pipeline->continue();

                if ([] !== $outbox->getEnvelopes()) {
                    $this->outboxStorage->create($queue, $messageId, $outbox);
                }
            });
        }

        $envelopes = $outbox->getEnvelopes();

        if ([] !== $envelopes) {
            // $this->transportPublisher->publish($envelopes);
            // $this->outboxStorage->empty($queue, $messageId);

            $this->transactionProvider->wrapInTransaction(function () use ($envelopes, $queue, $messageId): void {
                $this->outboxStorage->empty($queue, $messageId);
                $this->transportPublisher->publish($envelopes);
            });
        }

        return null;
    }
}

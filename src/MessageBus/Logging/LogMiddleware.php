<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;

final class LogMiddleware implements Middleware
{
    private readonly LoggerInterface $logger;

    public function __construct(LoggerInterface $logger = new NullLogger())
    {
        $this->logger = $logger;
    }

    /**
     * @throws \Throwable
     */
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        $this->logger->info('Start handle message {message_class}.', [
            'message_class' => $messageContext->getMessageClass(),
            'handler_id' => $pipeline->id(),
            'envelop' => $messageContext->getEnvelop(),
        ]);

        try {
            $result = $pipeline->continue();

            $this->logger->info('Message {message_class} success handled.', [
                'message_class' => $messageContext->getMessageClass(),
                'handler_id' => $pipeline->id(),
                'envelop' => $messageContext->getEnvelop(),
            ]);

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to handle {message_class}.', [
                'message_class' => $messageContext->getMessageClass(),
                'handler_id' => $pipeline->id(),
                'envelop' => $messageContext->getEnvelop(),
                'exception' => $e,
            ]);

            throw $e;
        }
    }
}

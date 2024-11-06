<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\Logging;

use Kenny1911\SisyphBus\Message\Message;
use Kenny1911\SisyphBus\MessageBus\Envelop;
use Kenny1911\SisyphBus\MessageBus\MessageContext;
use Kenny1911\SisyphBus\MessageBus\Middleware\Middleware;
use Kenny1911\SisyphBus\MessageBus\Middleware\Pipeline;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

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
            'envelop' => $messageContext->envelop,
        ]);

        try {
            $result = $pipeline->continue();

            $this->logger->info('Message {message_class} success handled.', [
                'message_class' => $messageContext->getMessageClass(),
                'handler_id' => $pipeline->id(),
                'envelop' => $messageContext->envelop,
            ]);

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to handle {message_class}.', [
                'message_class' => $messageContext->getMessageClass(),
                'handler_id' => $pipeline->id(),
                'envelop' => $messageContext->envelop,
                'exception' => $e,
            ]);

            throw $e;
        }
    }
}

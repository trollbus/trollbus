<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\Logging;

use Kenny1911\SisyphBus\Message\Message;
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
    public function handle(Message $message, Pipeline $pipeline): mixed
    {
        $this->logger->info('Start handle message {message_class}.', [
            'message_class' => $message::class,
            'message' => $message,
        ]);

        try {
            $result = $pipeline->continue();

            $this->logger->info('Message {message_class} success handled.', [
                'message_class' => $message::class,
                'message' => $message,
            ]);

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to handle {message_class}.', [
                'message_class' => $message::class,
                'message' => $message,
                'exception' => $e,
            ]);

            throw $e;
        }
    }
}

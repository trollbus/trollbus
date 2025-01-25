<?php

declare(strict_types=1);

namespace Trollbus\Async;

use Trollbus\MessageBus\Envelope;
use Trollbus\MessageBus\HandlerRegistry;
use Trollbus\MessageBus\MessageBus;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;

final class Consumer
{
    /** @var non-empty-string */
    public readonly string $queue;

    private readonly HandlerRegistry $handlerRegistry;

    /** @var iterable<Middleware> */
    private readonly iterable $middlewares;

    private readonly MessageBus $messageBus;

    /**
     * @param non-empty-string $queue
     * @param iterable<Middleware> $middlewares
     */
    public function __construct(
        string $queue,
        HandlerRegistry $handlerRegistry,
        iterable $middlewares,
        MessageBus $messageBus,
    ) {
        $this->queue = $queue;
        $this->handlerRegistry = $handlerRegistry;
        $this->middlewares = $middlewares;
        $this->messageBus = $messageBus;
    }

    public function handle(Envelope $envelop): void
    {
        $handler = $this->handlerRegistry->get($envelop->message::class);
        $messageContext = $this->messageBus->startContext($envelop);
        $messageContext->addAttributes(new Queue($this->queue));

        Pipeline::handle(
            $messageContext,
            $handler,
            $this->middlewares,
        );
    }
}

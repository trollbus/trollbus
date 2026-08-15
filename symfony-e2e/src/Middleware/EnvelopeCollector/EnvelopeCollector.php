<?php

declare(strict_types=1);

namespace App\Middleware\EnvelopeCollector;

use Trollbus\MessageBus\Envelope;
use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\Middleware;
use Trollbus\MessageBus\Middleware\Pipeline;

final class EnvelopeCollector implements Middleware
{
    /** @var list<Envelope> */
    private array $envelopes = [];

    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        $result = $pipeline->continue();

        $this->envelopes[] = $messageContext->getEnvelop();

        return $result;
    }

    public function pull(): ?Envelope
    {
        return array_pop($this->envelopes);
    }

    /**
     * @return list<Envelope>
     */
    public function pullAll(): array
    {
        $envelopes = $this->envelopes;
        $this->envelopes = [];

        return $envelopes;
    }
}

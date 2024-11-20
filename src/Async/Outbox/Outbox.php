<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\Async\Outbox;

use Kenny1911\SisyphBus\MessageBus\Envelope;
use Kenny1911\SisyphBus\MessageBus\InheritanceContextAttribute;

final class Outbox implements InheritanceContextAttribute
{
    /** @var list<Envelope> */
    private array $envelopes = [];

    /**
     * @return list<Envelope>
     */
    public function getEnvelopes(): array
    {
        return $this->envelopes;
    }

    public function addEnvelope(Envelope $envelop): void
    {
        if (!\in_array($envelop, $this->envelopes, true)) {
            $this->envelopes[] = $envelop;
        }
    }
}

<?php

declare(strict_types=1);

namespace Trollbus\Async\Outbox;

use Trollbus\MessageBus\Envelope;
use Trollbus\MessageBus\InheritanceContextAttribute;

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

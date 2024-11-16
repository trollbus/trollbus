<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\Async\Outbox;

use Kenny1911\SisyphBus\MessageBus\Envelop;
use Kenny1911\SisyphBus\MessageBus\InheritanceContextAttribute;

final class Outbox implements InheritanceContextAttribute
{
    /** @var list<Envelop> */
    private array $envelopes = [];

    /**
     * @return list<Envelop>
     */
    public function getEnvelopes(): array
    {
        return $this->envelopes;
    }

    public function addEnvelope(Envelop $envelop): void
    {
        if (!\in_array($envelop, $this->envelopes, true)) {
            $this->envelopes[] = $envelop;
        }
    }
}

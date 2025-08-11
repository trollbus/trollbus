<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\DeferredEvent;

use Trollbus\MessageBus\Middleware\Pipeline;
use Trollbus\MessageBus\ReadonlyMessageContext;

final class DeferredEventsStorage
{
    /** @var \WeakMap<ReadonlyMessageContext, list<Pipeline>> */
    private \WeakMap $parentMessageContextToEventsPipelines;

    public function __construct()
    {
        /** @var \WeakMap<ReadonlyMessageContext, list<Pipeline>> */
        $this->parentMessageContextToEventsPipelines = new \WeakMap();
    }

    public function addPipeline(ReadonlyMessageContext $parentMessagesContext, Pipeline $pipeline): void
    {
        $pipelines = $this->parentMessageContextToEventsPipelines[$parentMessagesContext] ?? [];
        $pipelines[] = $pipeline;
        $this->parentMessageContextToEventsPipelines[$parentMessagesContext] = $pipelines;
    }

    /**
     * @return list<Pipeline>
     */
    public function pullChildEventsPipelines(ReadonlyMessageContext $messageContext): array
    {
        /** @var list<Pipeline> Fix psalm bug, when return type detects as nullable */
        return $this->parentMessageContextToEventsPipelines[$messageContext] ?? [];
    }
}

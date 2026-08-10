<?php

declare(strict_types=1);

namespace Trollbus\Tests\Unit\MessageBus\MessageBusTestCases\EntityFactoryHandlerWithDeferredEvent;

use Trollbus\MessageBus\MessageContext;

final class Entity
{
    public static function create(CreateEntity $command, MessageContext $messageContext): self
    {
        $entity = new self();
        $messageContext->dispatch(new EntityCreated());

        return $entity;
    }
}

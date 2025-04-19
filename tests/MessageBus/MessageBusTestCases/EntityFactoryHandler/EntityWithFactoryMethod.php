<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\MessageBusTestCases\EntityFactoryHandler;

use Trollbus\MessageBus\MessageContext;

final class EntityWithFactoryMethod
{
    private function __construct(
        public readonly string $id,
        public readonly string $title,
    ) {}

    /**
     * @param MessageContext<void, CreateEntityWithFactoryMethod> $messageContext
     */
    public static function createEntity(CreateEntityWithFactoryMethod $command, MessageContext $messageContext): self
    {
        $entity = new self(
            id: $command->id,
            title: $command->title,
        );

        $messageContext->dispatch(new EntityWithFactoryMethodCreated(
            id: $entity->id,
            title: $entity->title,
        ));

        return $entity;
    }
}

<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\EntityHandler;

use Trollbus\Message\Message;
use Trollbus\MessageBus\Handler;
use Trollbus\MessageBus\MessageContext;

/**
 * @template TMessage of Message<void>
 *
 * @implements Handler<void, TMessage>
 */
final class EntityFactoryHandler implements Handler
{
    /**
     * @param non-empty-string $id
     * @param class-string $entityClass
     * @param non-empty-string $handlerMethod static handler method, that handle `Trollbus\Message\Message<void>`
     *                                        message and return entity instance
     */
    public function __construct(
        private readonly string $id,
        private readonly EntitySaver $saver,
        private readonly string $entityClass,
        private readonly string $handlerMethod,
    ) {}

    #[\Override]
    public function id(): string
    {
        return $this->id;
    }

    #[\Override]
    public function handle(MessageContext $messageContext): mixed
    {
        /**
         * @psalm-suppress MixedMethodCall
         * @var object $entity
         */
        $entity = $this->entityClass::{$this->handlerMethod}($messageContext->getMessage(), $messageContext);

        $this->saver->save($entity);

        return null;
    }
}

<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\EntityHandler;

use Trollbus\Message\Message;
use Trollbus\MessageBus\Handler;
use Trollbus\MessageBus\MessageContext;

/**
 * @template TResult
 * @template TMessage of Message<TResult>
 * @template TEntity of object
 *
 * @implements Handler<TResult, TMessage>
 */
final class EntityHandler implements Handler
{
    /**
     * @param non-empty-string $id
     * @param class-string $entityClass
     * @param non-empty-string $handlerMethod
     * @param non-empty-array<non-empty-string, non-empty-string> $findBy
     * @param non-empty-string|null $factoryMethod
     */
    public function __construct(
        private readonly string $id,
        private readonly EntityFinder $finder,
        private readonly CriteriaResolver $criteriaResolver,
        private readonly EntitySaver $saver,
        private readonly string $entityClass,
        private readonly string $handlerMethod,
        private readonly array $findBy,
        private readonly ?string $factoryMethod,
    ) {}

    #[\Override]
    public function id(): string
    {
        return $this->id;
    }

    #[\Override]
    public function handle(MessageContext $messageContext): mixed
    {
        $message = $messageContext->getMessage();

        $entity = $this->finder->findBy(
            $this->entityClass,
            $this->criteriaResolver->resolve($message, $this->findBy),
        );

        if (null === $entity) {
            if (null === $this->factoryMethod) {
                throw new EntityNotFound('Entity not found.');
            }

            /**
             * @psalm-suppress MixedMethodCall
             * @var TMessage $entity
             */
            $entity = $this->entityClass::{$this->factoryMethod}($message, $messageContext);
        }

        /**
         * @psalm-suppress MixedMethodCall
         * @var TResult $result
         */
        $result = $entity->{$this->handlerMethod}($message, $messageContext);
        $this->saver->save($entity);

        return $result;
    }
}

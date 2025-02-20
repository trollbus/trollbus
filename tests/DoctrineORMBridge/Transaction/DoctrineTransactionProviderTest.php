<?php

declare(strict_types=1);

namespace Trollbus\Tests\DoctrineORMBridge\Transaction;

use PHPUnit\Framework\TestCase;
use Trollbus\DoctrineORMBridge\Transaction\DoctrineTransactionProvider;
use Trollbus\MessageBus\Handler\CallableHandler;
use Trollbus\MessageBus\HandlerRegistry\ClassStringMap;
use Trollbus\MessageBus\HandlerRegistry\ClassStringMapHandlerRegistry;
use Trollbus\MessageBus\MessageBus;
use Trollbus\MessageBus\Transaction\WrapInTransactionMiddleware;
use Trollbus\Tests\DoctrineORMBridge\ManagerRegistry;

final class DoctrineTransactionProviderTest extends TestCase
{
    private ManagerRegistry $doctrine;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = new ManagerRegistry(__DIR__);
        $this->doctrine->createSchema();
    }

    public function test(): void
    {
        /** @psalm-suppress InvalidArgument CreateEntity<void>, but return type of CallableHandler is never, because it throws ErrorAfterEntityCreated */
        $messageBus = new MessageBus(
            handlerRegistry: new ClassStringMapHandlerRegistry(
                (new ClassStringMap())
                    ->with(CreateEntity::class, new CallableHandler('create-entity', function (CreateEntity $command): void {
                        $entity = new Entity(
                            id: $command->id,
                            title: $command->title,
                        );

                        $em = $this->doctrine->getManager();
                        $em->persist($entity);
                        $em->flush();

                        $em->clear();

                        // Check, that entity was saved
                        $savedEntity = $em->find(Entity::class, $command->id);

                        $this->assertInstanceOf(Entity::class, $savedEntity);
                        $this->assertSame($command->id, $savedEntity->getId());
                        $this->assertSame($command->title, $savedEntity->getTitle());

                        throw new ErrorAfterEntityCreated();
                    })),
            ),
            middlewares: [
                new WrapInTransactionMiddleware(
                    new DoctrineTransactionProvider($this->doctrine),
                ),
            ],
        );

        try {
            $messageBus->dispatch(new CreateEntity('1', 'Title'));
        } catch (ErrorAfterEntityCreated) {
        }

        $this->doctrine->resetManager();
        self::assertNull($this->doctrine->getManager()->find(Entity::class, '1'));
    }
}

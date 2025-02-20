<?php

declare(strict_types=1);

namespace Trollbus\Tests\DoctrineORMBridge\ResetEntityManager;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Trollbus\DoctrineORMBridge\ResetEntityManager\ResetEntityManagerMiddleware;
use Trollbus\MessageBus\Handler\CallableHandler;
use Trollbus\MessageBus\HandlerRegistry\ClassStringMap;
use Trollbus\MessageBus\HandlerRegistry\ClassStringMapHandlerRegistry;
use Trollbus\MessageBus\MessageBus;
use Trollbus\Tests\DoctrineORMBridge\ManagerRegistry;

final class ResetEntityManagerMiddlewareTest extends TestCase
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
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManager();
        $em->close();
        unset($em);

        $messageBus = new MessageBus(
            handlerRegistry: new ClassStringMapHandlerRegistry(
                (new ClassStringMap())
                    ->with(
                        messageClass: CreateEntity::class,
                        handler: new CallableHandler('create-entity', function (CreateEntity $command): void {
                            $entity = new Entity(
                                id: $command->id,
                                title: $command->title,
                            );

                            $em = $this->doctrine->getManager();
                            $em->persist($entity);
                            $em->flush();
                        }),
                    ),
            ),
            middlewares: [
                new ResetEntityManagerMiddleware($this->doctrine),
            ],
        );

        $messageBus->dispatch(new CreateEntity('1', 'Title'));

        $entity = $this->doctrine->getManager()->find(Entity::class, '1');

        self::assertInstanceOf(Entity::class, $entity);
        self::assertSame('1', $entity->getId());
        self::assertSame('Title', $entity->getTitle());
    }
}

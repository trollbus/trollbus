<?php

declare(strict_types=1);

namespace Trollbus\Tests\DoctrineORMBridge\EntityHandler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use PHPUnit\Framework\TestCase;
use Trollbus\DoctrineORMBridge\EntityHandler\DoctrineEntityFinder;
use Trollbus\DoctrineORMBridge\EntityHandler\DoctrineEntitySaver;
use Trollbus\MessageBus\EntityHandler\EntityHandler;
use Trollbus\MessageBus\EntityHandler\EntityNotFound;
use Trollbus\MessageBus\EntityHandler\PropertyCriteriaResolver;
use Trollbus\MessageBus\HandlerRegistry\ClassStringMap;
use Trollbus\MessageBus\HandlerRegistry\ClassStringMapHandlerRegistry;
use Trollbus\MessageBus\MessageBus;
use Trollbus\Tests\DoctrineORMBridge\ManagerRegistry;

final class DoctrineEntityFinderAndSaverTest extends TestCase
{
    private ManagerRegistry $doctrine;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = new ManagerRegistry(__DIR__);
        $this->doctrine->createSchema();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testNewAndSaverFlush(): void
    {
        $messageBus = $this->createMessageBus(useFactoryMethod: true, saverFlush: true);
        $messageBus->dispatch(new EditEntity('1', 'Title'));

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManager();
        self::assertCount(0, $em->getUnitOfWork()->getScheduledEntityInsertions());

        $em->clear();

        $entity = $em->find(Entity::class, '1');

        self::assertInstanceOf(Entity::class, $entity);
        self::assertSame('1', $entity->getId());
        self::assertSame('Title', $entity->getTitle());
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testNewAndSaverNotFlush(): void
    {
        $messageBus = $this->createMessageBus(useFactoryMethod: true, saverFlush: false);
        $messageBus->dispatch(new EditEntity('1', 'Title'));

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManager();
        self::assertCount(1, $em->getUnitOfWork()->getScheduledEntityInsertions());

        $em->flush();
        $em->clear();

        $entity = $em->find(Entity::class, '1');

        self::assertInstanceOf(Entity::class, $entity);
        self::assertSame('1', $entity->getId());
        self::assertSame('Title', $entity->getTitle());
    }

    public function testNewNoFactoryMethod(): void
    {
        $this->expectException(EntityNotFound::class);

        $messageBus = $this->createMessageBus(useFactoryMethod: false, saverFlush: true);
        $messageBus->dispatch(new EditEntity('1', 'Title'));
    }

    public function testEditAndSaverFlush(): void
    {
        $entity = new Entity('1');
        $entity->editEntity(new EditEntity('1', 'Old title'));

        $em = $this->doctrine->getManager();
        $em->persist($entity);
        $em->flush();
        $em->clear();

        $messageBus = $this->createMessageBus(useFactoryMethod: true, saverFlush: true);
        $messageBus->dispatch(new EditEntity('1', 'Title'));
        $em->clear();

        $savedEntity = $em->find(Entity::class, '1');

        self::assertInstanceOf(Entity::class, $savedEntity);
        self::assertNotSame($entity, $savedEntity);
        self::assertSame('1', $savedEntity->getId());
        self::assertSame('Title', $savedEntity->getTitle());

        self::assertSame('Old title', $entity->getTitle());
    }

    private function createMessageBus(bool $useFactoryMethod, bool $saverFlush): MessageBus
    {
        return new MessageBus(
            handlerRegistry: new ClassStringMapHandlerRegistry(
                (new ClassStringMap())->with(
                    messageClass: EditEntity::class,
                    handler: new EntityHandler(
                        id: 'entity',
                        finder: new DoctrineEntityFinder($this->doctrine),
                        criteriaResolver: new PropertyCriteriaResolver(),
                        saver: new DoctrineEntitySaver($this->doctrine, $saverFlush),
                        entityClass: Entity::class,
                        handlerMethod: 'editEntity',
                        findBy: ['id' => 'id'],
                        factoryMethod: $useFactoryMethod ? 'createFromCommand' : null,
                    ),
                ),
            ),
        );
    }
}

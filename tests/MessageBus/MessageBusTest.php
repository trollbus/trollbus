<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Trollbus\Message\Message;
use Trollbus\MessageBus\CreatedAt\CreatedAt;
use Trollbus\MessageBus\CreatedAt\CreatedAtMiddleware;
use Trollbus\MessageBus\EntityHandler\EntityHandler;
use Trollbus\MessageBus\EntityHandler\EntityNotFound;
use Trollbus\MessageBus\EntityHandler\PropertyCriteriaResolver;
use Trollbus\MessageBus\HandlerRegistry\ClassStringMap;
use Trollbus\MessageBus\HandlerRegistry\ClassStringMapHandlerRegistry;
use Trollbus\MessageBus\Logging\LogMiddleware;
use Trollbus\MessageBus\MessageBus;
use Trollbus\MessageBus\MessageId\CausationId;
use Trollbus\MessageBus\MessageId\CausationIdMiddleware;
use Trollbus\MessageBus\MessageId\CorrelationId;
use Trollbus\MessageBus\MessageId\CorrelationIdMiddleware;
use Trollbus\MessageBus\MessageId\MessageIdMiddleware;
use Trollbus\MessageBus\MessageId\MessageIdNotSet;
use Trollbus\MessageBus\ReadonlyMessageContext;
use Trollbus\MessageBus\Transaction\FakeTransactionProvider;
use Trollbus\MessageBus\Transaction\InTransaction;
use Trollbus\MessageBus\Transaction\WrapInTransactionMiddleware;
use Trollbus\Tests\MessageBus\MessageBusTestCases\EntityHandler\EditEntity;
use Trollbus\Tests\MessageBus\MessageBusTestCases\EntityHandler\Entity;
use Trollbus\Tests\MessageBus\MessageBusTestCases\EntityHandler\EntityEdited;
use Trollbus\Tests\MessageBus\MessageBusTestCases\EntityHandler\InMemoryEntityFinderAndSaver;
use Trollbus\Tests\MessageBus\MessageBusTestCases\NestedDispatch\NestedDispatchLevel1;
use Trollbus\Tests\MessageBus\MessageBusTestCases\NestedDispatch\NestedDispatchLevel1Handler;
use Trollbus\Tests\MessageBus\MessageBusTestCases\NestedDispatch\NestedDispatchLevel2;
use Trollbus\Tests\MessageBus\MessageBusTestCases\NestedDispatch\NestedDispatchLevel2Handler;
use Trollbus\Tests\MessageBus\MessageBusTestCases\NestedDispatch\NestedDispatchLevel3;
use Trollbus\Tests\MessageBus\MessageBusTestCases\NestedDispatch\NestedDispatchLevel3Handler;
use Trollbus\Tests\MessageBus\MessageBusTestCases\SimpleMessage\SimpleMessage;
use Trollbus\Tests\MessageBus\MessageBusTestCases\SimpleMessage\SimpleMessageHandler;
use Trollbus\Tests\MessageBus\MessageBusTestCases\SimpleMessage\SimpleMessageResult;
use Trollbus\Tests\MessageBus\MessageContextStack\MessageContextStack;
use Trollbus\Tests\MessageBus\MessageContextStack\MessageContextStackMiddleware;
use Trollbus\Tests\MessageBus\MessageId\SequenceMessageIdGenerator;
use Trollbus\Tests\Psr\Clock\FakeClock;
use Trollbus\Tests\Psr\Log\InMemoryLogger;

final class MessageBusTest extends TestCase
{
    private FakeClock $clock;

    private SequenceMessageIdGenerator $messageIdGenerator;

    private InMemoryLogger $logger;

    private MessageContextStack $messageContextStack;

    #[\Override]
    protected function setUp(): void
    {
        $this->clock = new FakeClock(new \DateTimeImmutable('2025-01-01 00:00:00'));
        $this->messageIdGenerator = new SequenceMessageIdGenerator();
        $this->logger = new InMemoryLogger();
        $this->messageContextStack = new MessageContextStack();
    }

    /**
     * @throws MessageIdNotSet
     */
    public function testSimpleMessage(): void
    {
        $message = new SimpleMessage(
            foo: 123,
            bar: 456,
        );

        $messageBus = $this->createMessageBus(
            ClassStringMap::createWith(SimpleMessage::class, new SimpleMessageHandler()),
        );
        $result = $messageBus->dispatch($message);

        // Assert result
        self::assertInstanceOf(SimpleMessageResult::class, $result);
        self::assertSame(123, $result->foo);
        self::assertSame(456, $result->bar);

        // Assert message contexts
        $messageContexts = $this->messageContextStack->pull();

        self::assertCount(1, $messageContexts);

        self::assertMessageContext(
            messageContext: $messageContexts[0],
            messageClass: SimpleMessage::class,
            createdAt: new \DateTimeImmutable('2025-01-01 00:00:00'),
            messageId: '1',
            correlationId: '1',
            causationId: null,
        );
        $this->assertLogLevels([LogLevel::INFO, LogLevel::INFO]);
    }

    /**
     * @throws MessageIdNotSet
     */
    public function testNestedDispatch(): void
    {
        $message = new NestedDispatchLevel1();

        $messageBus = $this->createMessageBus(
            (new ClassStringMap())
                ->with(NestedDispatchLevel1::class, new NestedDispatchLevel1Handler())
                ->with(NestedDispatchLevel2::class, new NestedDispatchLevel2Handler())
                ->with(NestedDispatchLevel3::class, new NestedDispatchLevel3Handler()),
        );

        $messageBus->dispatch($message);
        $messageContexts = $this->messageContextStack->pull();

        self::assertCount(3, $messageContexts);

        self::assertMessageContext(
            messageContext: $messageContexts[0],
            messageClass: NestedDispatchLevel1::class,
            createdAt: new \DateTimeImmutable('2025-01-01 00:00:00'),
            messageId: '1',
            correlationId: '1',
            causationId: null,
        );
        self::assertMessageContext(
            messageContext: $messageContexts[1],
            messageClass: NestedDispatchLevel2::class,
            createdAt: new \DateTimeImmutable('2025-01-01 00:00:00'),
            messageId: '2',
            correlationId: '1',
            causationId: '1',
        );
        self::assertMessageContext(
            messageContext: $messageContexts[2],
            messageClass: NestedDispatchLevel3::class,
            createdAt: new \DateTimeImmutable('2025-01-01 00:00:00'),
            messageId: '3',
            correlationId: '1',
            causationId: '2',
        );
        $this->assertLogLevels([LogLevel::INFO, LogLevel::INFO, LogLevel::INFO, LogLevel::INFO, LogLevel::INFO, LogLevel::INFO]);
    }

    public function testEntityHandlerThrowsEntityNotFound(): void
    {
        $this->expectException(EntityNotFound::class);

        $finderAndSaver = new InMemoryEntityFinderAndSaver();
        $handler = new EntityHandler(
            id: EntityHandler::class,
            finder: $finderAndSaver,
            criteriaResolver: new PropertyCriteriaResolver(),
            saver: $finderAndSaver,
            entityClass: Entity::class,
            handlerMethod: 'edit',
            findBy: ['id' => 'id'],
            factoryMethod: null,
        );

        $message = new EditEntity(
            id: '1',
            title: 'Title',
            description: 'Description',
        );

        $messageBus = $this->createMessageBus(
            (new ClassStringMap())->with(EditEntity::class, $handler),
        );

        $messageBus->dispatch($message);
    }

    /**
     * @throws MessageIdNotSet
     */
    public function testEntityHandlerWithFactoryMethod(): void
    {
        $finderAndSaver = new InMemoryEntityFinderAndSaver();
        $handler = new EntityHandler(
            id: EntityHandler::class,
            finder: $finderAndSaver,
            criteriaResolver: new PropertyCriteriaResolver(),
            saver: $finderAndSaver,
            entityClass: Entity::class,
            handlerMethod: 'edit',
            findBy: ['id' => 'id'],
            factoryMethod: 'create',
        );

        $message = new EditEntity(
            id: '1',
            title: 'Title',
            description: 'Description',
        );

        $messageBus = $this->createMessageBus(
            (new ClassStringMap())->with(EditEntity::class, $handler),
        );

        $messageBus->dispatch($message);
        $messageContexts = $this->messageContextStack->pull();
        $entity = $finderAndSaver->findBy(Entity::class, ['id' => '1']);

        self::assertInstanceOf(Entity::class, $entity);
        self::assertSame('1', $entity->getId());
        self::assertSame('Title', $entity->getTitle());
        self::assertSame('Description', $entity->getDescription());

        self::assertSame(1, $finderAndSaver->countEntitySaves($entity));

        self::assertCount(2, $messageContexts);
        self::assertMessageContext(
            messageContext: $messageContexts[0],
            messageClass: EditEntity::class,
            createdAt: new \DateTimeImmutable('2025-01-01 00:00:00'),
            messageId: '1',
            correlationId: '1',
            causationId: null,
        );
        self::assertMessageContext(
            messageContext: $messageContexts[1],
            messageClass: EntityEdited::class,
            createdAt: new \DateTimeImmutable('2025-01-01 00:00:00'),
            messageId: '2',
            correlationId: '1',
            causationId: '1',
        );
    }

    /**
     * @throws MessageIdNotSet
     */
    public function testEntityHandlerEditExisting(): void
    {
        $finderAndSaver = new InMemoryEntityFinderAndSaver();
        $entity = Entity::create(new EditEntity('1', 'Old Title', 'Old Description'));
        $finderAndSaver->save($entity);

        $handler = new EntityHandler(
            id: EntityHandler::class,
            finder: $finderAndSaver,
            criteriaResolver: new PropertyCriteriaResolver(),
            saver: $finderAndSaver,
            entityClass: Entity::class,
            handlerMethod: 'edit',
            findBy: ['id' => 'id'],
            factoryMethod: null,
        );

        $message = new EditEntity(
            id: '1',
            title: 'Title',
            description: 'Description',
        );

        $messageBus = $this->createMessageBus(
            (new ClassStringMap())->with(EditEntity::class, $handler),
        );

        $messageBus->dispatch($message);
        $messageContexts = $this->messageContextStack->pull();

        self::assertSame('1', $entity->getId());
        self::assertSame('Title', $entity->getTitle());
        self::assertSame('Description', $entity->getDescription());

        self::assertSame(2, $finderAndSaver->countEntitySaves($entity));

        self::assertCount(2, $messageContexts);
        self::assertMessageContext(
            messageContext: $messageContexts[0],
            messageClass: EditEntity::class,
            createdAt: new \DateTimeImmutable('2025-01-01 00:00:00'),
            messageId: '1',
            correlationId: '1',
            causationId: null,
        );
        self::assertMessageContext(
            messageContext: $messageContexts[1],
            messageClass: EntityEdited::class,
            createdAt: new \DateTimeImmutable('2025-01-01 00:00:00'),
            messageId: '2',
            correlationId: '1',
            causationId: '1',
        );
    }

    private function createMessageBus(ClassStringMap $messageClassToHandler): MessageBus
    {
        return new MessageBus(
            handlerRegistry: new ClassStringMapHandlerRegistry($messageClassToHandler),
            middlewares: [
                new CreatedAtMiddleware($this->clock),
                new MessageIdMiddleware($this->messageIdGenerator),
                new CorrelationIdMiddleware(),
                new CausationIdMiddleware(),
                new LogMiddleware($this->logger),
                new WrapInTransactionMiddleware(new FakeTransactionProvider()),
                new MessageContextStackMiddleware($this->messageContextStack),
            ],
        );
    }

    /**
     * @param class-string<Message> $messageClass
     * @param non-empty-string $messageId
     * @param non-empty-string $correlationId
     * @param non-empty-string $causationId
     *
     * @throws MessageIdNotSet
     */
    private static function assertMessageContext(
        ReadonlyMessageContext $messageContext,
        string $messageClass,
        \DateTimeImmutable $createdAt,
        string $messageId,
        string $correlationId,
        ?string $causationId,
    ): void {
        self::assertSame($messageClass, $messageContext->getMessageClass());
        self::assertSame($messageId, $messageContext->getMessageId());
        self::assertSame($correlationId, $messageContext->getStamp(CorrelationId::class)?->correlationId);
        self::assertSame($causationId, $messageContext->getStamp(CausationId::class)?->causationId);
        self::assertTrue($messageContext->hasAttribute(InTransaction::class));
        self::assertEquals($createdAt, $messageContext->getStamp(CreatedAt::class)?->createdAt);
    }

    /**
     * @param list<LogLevel::*> $logLevels
     */
    private function assertLogLevels(array $logLevels): void
    {
        $actualLogLevels = array_map(static fn(array $log): string => $log[0], $this->logger->getLogs());

        self::assertSame($logLevels, $actualLogLevels);
    }
}

<?php

declare(strict_types=1);

namespace Trollbus\Tests\TrollbusBundle;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Trollbus\DoctrineORMBridge\Flusher\Flushed;
use Trollbus\MessageBus\CreatedAt\CreatedAt;
use Trollbus\MessageBus\MessageBus;
use Trollbus\MessageBus\MessageId\CausationId;
use Trollbus\MessageBus\MessageId\CorrelationId;
use Trollbus\MessageBus\MessageId\MessageId;
use Trollbus\MessageBus\MessageId\MessageIdNotSet;
use Trollbus\MessageBus\Transaction\InTransaction;
use Trollbus\Tests\DoctrineORMBridge\EntityHandler\EditEntity;
use Trollbus\Tests\DoctrineORMBridge\EntityHandler\Entity;
use Trollbus\Tests\DoctrineORMBridge\ManagerRegistry;
use Trollbus\Tests\MessageBus\MessageBusAssert;
use Trollbus\Tests\MessageBus\MessageBusTestCases\EventHandler\SomeEvent;
use Trollbus\Tests\MessageBus\MessageBusTestCases\EventHandler\SomeEventListener;
use Trollbus\Tests\MessageBus\MessageBusTestCases\HandlerMiddleware\HandlerMiddleware;
use Trollbus\Tests\MessageBus\MessageBusTestCases\HandlerMiddleware\HandlerMiddlewareStamp;
use Trollbus\Tests\MessageBus\MessageBusTestCases\SimpleMessage\SimpleMessage;
use Trollbus\Tests\MessageBus\MessageBusTestCases\SimpleMessage\SimpleMessageHandler;
use Trollbus\Tests\MessageBus\MessageBusTestCases\SimpleMessage\SimpleMessageManager;
use Trollbus\Tests\MessageBus\MessageBusTestCases\SimpleMessage\SimpleMessageResult;
use Trollbus\Tests\MessageBus\MessageContextStack\MessageContextStack;
use Trollbus\Tests\MessageBus\MessageContextStack\MessageContextStackMiddleware;
use Trollbus\Tests\MessageBus\MessageId\SequenceMessageIdGenerator;
use Trollbus\Tests\Psr\Clock\FakeClock;
use Trollbus\Tests\Psr\Log\InMemoryLogger;
use Trollbus\TrollbusBundle\DependencyInjection\MessageBusConfigurator;
use Trollbus\TrollbusBundle\TrollbusBundle;
use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

final class TrollbusBundleTest extends TestCase
{
    use MessageBusAssert;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws MessageIdNotSet
     */
    public function testConfigureHandler(): void
    {
        $container = $this->createContainerWithAllEnabledConfigs(static function (ContainerConfigurator $di): void {
            $di->services()
                ->set(SimpleMessageHandler::class);

            MessageBusConfigurator::create($di)
                ->handler(SimpleMessage::class, SimpleMessageHandler::class);

        });
        /** @var MessageBus $messageBus */
        $messageBus = $container->get('trollbus');

        $result = $messageBus->dispatch(new SimpleMessage(foo: 123, bar: 456));

        self::assertInstanceOf(SimpleMessageResult::class, $result);
        self::assertSame(123, $result->foo);
        self::assertSame(456, $result->bar);

        // Assert message contexts
        /** @var MessageContextStack $messageContextStack */
        $messageContextStack = $container->get(MessageContextStack::class);
        $messageContexts = $messageContextStack->pull();

        self::assertCount(1, $messageContexts);

        self::assertMessageContext(
            messageContext: $messageContexts[0],
            messageClass: SimpleMessage::class,
            createdAt: new \DateTimeImmutable('2025-01-01 00:00:00'),
            messageId: '1',
            correlationId: '1',
            causationId: null,
        );
        self::assertTrue($messageContexts[0]->hasAttribute(InTransaction::class));
        self::assertTrue($messageContexts[0]->hasAttribute(Flushed::class));
        /** @var InMemoryLogger $logger */
        $logger = $container->get('logger');
        self::assertLogLevels([LogLevel::INFO, LogLevel::INFO], $logger);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function testConfigureHandlerWithDisabledAllConfigs(): void
    {
        $container = $this->createContainer(static function (ContainerConfigurator $di): void {
            $di->services()
                ->set('logger', InMemoryLogger::class)
                    ->public();

            $di->extension('trollbus', [
                'created_at' => [
                    'enabled' => false,
                ],
                'logger' => [
                    'enabled' => false,
                ],
                'message_id' => [
                    'enabled' => false,
                ],
                'transaction' => [
                    'enabled' => false,
                ],
                'entity_handler' => [
                    'enabled' => false,
                ],
                'doctrine_orm_bridge' => [
                    'enabled' => false,
                ],
            ]);

            $di->services()
                ->set(SimpleMessageHandler::class);

            MessageBusConfigurator::create($di)
                ->handler(SimpleMessage::class, SimpleMessageHandler::class);
        });
        /** @var MessageBus $messageBus */
        $messageBus = $container->get('trollbus');

        $result = $messageBus->dispatch(new SimpleMessage(foo: 123, bar: 456));

        self::assertInstanceOf(SimpleMessageResult::class, $result);
        self::assertSame(123, $result->foo);
        self::assertSame(456, $result->bar);

        // Assert message contexts
        /** @var MessageContextStack $messageContextStack */
        $messageContextStack = $container->get(MessageContextStack::class);
        $messageContexts = $messageContextStack->pull();

        self::assertCount(1, $messageContexts);

        // No added stamps and attributes
        self::assertFalse($messageContexts[0]->hasStamp(CreatedAt::class));
        self::assertFalse($messageContexts[0]->hasStamp(MessageId::class));
        self::assertFalse($messageContexts[0]->hasStamp(CorrelationId::class));
        self::assertFalse($messageContexts[0]->hasStamp(CausationId::class));
        self::assertFalse($messageContexts[0]->hasAttribute(InTransaction::class));
        self::assertFalse($messageContexts[0]->hasAttribute(Flushed::class));
        /** @var InMemoryLogger $logger */
        $logger = $container->get('logger');
        self::assertLogLevels([], $logger);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testConfigureCallableHandler(): void
    {
        $container = $this->createContainerWithAllEnabledConfigs(static function (ContainerConfigurator $di): void {
            $di->services()
                ->set(SimpleMessageManager::class);

            MessageBusConfigurator::create($di)
                ->callableHandler(
                    message: SimpleMessage::class,
                    service: SimpleMessageManager::class,
                    method: 'handleMessage',
                    handlerId: 'simple-message',
                );
        });
        /** @var MessageBus $messageBus */
        $messageBus = $container->get('trollbus');

        $result = $messageBus->dispatch(new SimpleMessage(foo: 123, bar: 456));

        self::assertInstanceOf(SimpleMessageResult::class, $result);
        self::assertSame(123, $result->foo);
        self::assertSame(456, $result->bar);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testHandlerWithMiddleware(): void
    {
        $container = $this->createContainerWithAllEnabledConfigs(static function (ContainerConfigurator $di): void {
            $di->services()
                ->set(HandlerMiddleware::class)
                ->set(SimpleMessageManager::class);

            MessageBusConfigurator::create($di)
                ->callableHandler(
                    message: SimpleMessage::class,
                    service: SimpleMessageManager::class,
                    method: 'handleMessage',
                    handlerId: 'simple-message',
                    middlewares: [HandlerMiddleware::class],
                );
        });
        /** @var MessageBus $messageBus */
        $messageBus = $container->get('trollbus');

        $messageBus->dispatch(new SimpleMessage(foo: 123, bar: 456));

        // Assert, that HandlerMiddleware run
        /** @var MessageContextStack $messageContextStack */
        $messageContextStack = $container->get(MessageContextStack::class);
        $messageContexts = $messageContextStack->pull();

        self::assertTrue($messageContexts[0]->hasStamp(HandlerMiddlewareStamp::class));
    }

    public function testManyMessageHandlersThrowsException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(\sprintf('Non-event message %s must have 1 handler, got %s', SimpleMessage::class, 2));

        $this->createContainerWithAllEnabledConfigs(static function (ContainerConfigurator $di): void {
            $di->services()
                ->set('message_handler_1', SimpleMessageHandler::class)
                ->set('message_handler_2', SimpleMessageHandler::class);

            MessageBusConfigurator::create($di)
                ->handler(SimpleMessage::class, 'message_handler_1')
                ->handler(SimpleMessage::class, 'message_handler_2');
        });
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testManyEventHandlers(): void
    {
        $container = $this->createContainerWithAllEnabledConfigs(static function (ContainerConfigurator $di): void {
            $di->services()
                ->set(SomeEventListener::class)
                    ->public();

            MessageBusConfigurator::create($di)
                ->callableHandler(SomeEvent::class, SomeEventListener::class, 'listener1')
                ->callableHandler(SomeEvent::class, SomeEventListener::class, 'listener2');
        });
        /** @var MessageBus $messageBus */
        $messageBus = $container->get('trollbus');
        $event = new SomeEvent();

        $messageBus->dispatch($event);

        // Assert dispatched event
        /** @var SomeEventListener $listener */
        $listener = $container->get(SomeEventListener::class);
        self::assertSame([$event], $listener->getListener1Events());
        self::assertSame([$event], $listener->getListener2Events());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testNoEventHandlers(): void
    {
        $container = $this->createContainerWithAllEnabledConfigs();
        /** @var MessageBus $messageBus */
        $messageBus = $container->get('trollbus');
        $event = new SomeEvent();

        $messageBus->dispatch($event);

        self::assertTrue(true);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testEntityHandlerFromDoctrineORMBridge(): void
    {
        $container = $this->createContainerWithAllEnabledConfigs(static function (ContainerConfigurator $di): void {
            $messageBusConfigurator = MessageBusConfigurator::create($di);

            $messageBusConfigurator->entityHandler(
                message: EditEntity::class,
                entityClass: Entity::class,
                handlerMethod: 'editEntity',
                findBy: ['id' => 'id'],
                factoryMethod: 'createFromCommand',
            );
        });
        /** @var MessageBus $messageBus */
        $messageBus = $container->get('trollbus');
        /** @var ManagerRegistry $doctrine */
        $doctrine = $container->get('doctrine');
        $doctrine->createSchema();

        self::assertNull($doctrine->getManager()->find(Entity::class, '1'));

        // First dispatch message - create entity
        $messageBus->dispatch(new EditEntity('1', 'Title 1'));
        $entity = $doctrine->getManager()->find(Entity::class, '1');
        self::assertInstanceOf(Entity::class, $entity);
        self::assertSame('1', $entity->getId());
        self::assertSame('Title 1', $entity->getTitle());
        $doctrine->resetManager();

        // Second dispatch message - edit existing entity
        $messageBus->dispatch(new EditEntity('1', 'Title 2'));
        $entity = $doctrine->getManager()->find(Entity::class, '1');
        self::assertInstanceOf(Entity::class, $entity);
        self::assertSame('1', $entity->getId());
        self::assertSame('Title 2', $entity->getTitle());
    }

    /**
     * @param callable(ContainerConfigurator):void|null $configure
     */
    private function createContainerWithAllEnabledConfigs(?callable $configure = null): ContainerInterface
    {
        return $this->createContainer(static function (ContainerConfigurator $di) use ($configure): void {
            // Configure bundle
            $di->services()
                ->set('doctrine', ManagerRegistry::class)
                    ->args([
                        __DIR__ . '/../DoctrineORMBridge/EntityHandler/',
                    ])
                    ->public()
                ->set('clock', FakeClock::class)
                    ->args([
                        inline_service(\DateTimeImmutable::class)
                            ->args([
                                '2025-01-01 00:00:00',
                            ]),
                    ])
                    ->public()
                ->set('logger', InMemoryLogger::class)
                    ->public()
                ->set(SequenceMessageIdGenerator::class)
                    ->public();

            $di->extension('trollbus', [
                'created_at' => [
                    'enabled' => true,
                    'clock' => 'clock',
                ],
                'logger' => [
                    'enabled' => true,
                ],
                'message_id' => [
                    'enabled' => true,
                    'generator' => SequenceMessageIdGenerator::class,
                ],
                'transaction' => [
                    'enabled' => true,
                ],
                'entity_handler' => [
                    'enabled' => true,
                ],
                'doctrine_orm_bridge' => [
                    'enabled' => true,
                ],
            ]);

            if (null !== $configure) {
                $configure($di);
            }
        });
    }

    /**
     * @param callable(ContainerConfigurator):void|null $configure
     */
    private function createContainer(?callable $configure = null): ContainerInterface
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', __DIR__ . '/../../var/TrollbusBundle/cache');

        $bundle = new TrollbusBundle();
        $bundle->build($container);
        $container->registerExtension($bundle->getContainerExtension() ?? throw new \LogicException('No bundle extension.'));

        $instanceof = [];
        $di = new ContainerConfigurator(
            container: $container,
            loader: new PhpFileLoader(
                container: $container,
                locator: new FileLocator(__DIR__),
            ),
            instanceof: $instanceof,
            path: __FILE__,
            file: __FILE__,
        );

        if (null !== $configure) {
            $configure($di);
        }

        // Configure MessageBusContextStack service
        $di->services()
            ->set(MessageContextStack::class)
            ->public()
            ->set(MessageContextStackMiddleware::class)
            ->args([
                service(MessageContextStack::class),
            ]);
        MessageBusConfigurator::create($di)->middleware(MessageContextStackMiddleware::class);

        $container->compile();

        return $container;
    }
}

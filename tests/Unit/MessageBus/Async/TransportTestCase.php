<?php

declare(strict_types=1);

namespace Trollbus\Tests\Unit\MessageBus\Async;

use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;
use Trollbus\MessageBus\Async\Consumer;
use Trollbus\MessageBus\Async\Exchange\AddExchangeMiddleware;
use Trollbus\MessageBus\Async\Exchange\MessageClassBasedExchangeResolver;
use Trollbus\MessageBus\Async\Publisher;
use Trollbus\MessageBus\Async\TransportConsumer;
use Trollbus\MessageBus\Async\TransportPublisher;
use Trollbus\MessageBus\Async\TransportSetup;
use Trollbus\MessageBus\Envelope;
use Trollbus\MessageBus\Handler\EventHandler;
use Trollbus\MessageBus\HandlerRegistry\ClassStringMap;
use Trollbus\MessageBus\HandlerRegistry\ClassStringMapHandlerRegistry;
use Trollbus\MessageBus\MessageBus;
use Trollbus\MessageBus\MessageId\CausationIdMiddleware;
use Trollbus\MessageBus\MessageId\CorrelationIdMiddleware;
use Trollbus\MessageBus\MessageId\MessageId;
use Trollbus\MessageBus\MessageId\MessageIdMiddleware;
use Trollbus\MessageBus\MessageId\RandomMessageIdGenerator;
use Trollbus\Tests\Unit\MessageBus\Async\TransportTestCase\TransportTestEvent;
use Trollbus\Tests\Unit\MessageBus\Async\TransportTestCase\TransportTestEventHandler;

abstract class TransportTestCase extends TestCase
{
    final public function test(): void
    {
        [$transportPublisher, $transportConsumer, $transportSetup] = $this->createTransport();

        $exchangeResolver = new MessageClassBasedExchangeResolver();
        $exchange = $exchangeResolver->resolve(TransportTestEvent::class);

        $transportSetup->setup([$exchange => ['foo.async1_queue', 'foo.async2_queue']]);

        $syncHandler = new TransportTestEventHandler('foo.sync');

        $async1Handler = new TransportTestEventHandler('foo.async1');
        $async1Publisher = new Publisher(id: 'foo.async1', publisher: $transportPublisher);

        $async2Handler = new TransportTestEventHandler('foo.async2');
        $async2Publisher = new Publisher(id: 'foo.async2', publisher: $transportPublisher);

        $bus = new MessageBus(
            handlerRegistry: new ClassStringMapHandlerRegistry(ClassStringMap::create()->with(
                messageClass: TransportTestEvent::class,
                handler: new EventHandler([
                    $syncHandler,
                    $async1Publisher,
                    $async2Publisher,
                ]),
            )),
            middlewares: [
                new MessageIdMiddleware(new RandomMessageIdGenerator()),
                new CorrelationIdMiddleware(),
                new CausationIdMiddleware(),
                new AddExchangeMiddleware($exchangeResolver),
            ],
        );

        $async1Consumer = new Consumer(
            queue: 'foo.async1_queue',
            handlerRegistry: new ClassStringMapHandlerRegistry(ClassStringMap::create()->with(TransportTestEvent::class, $async1Handler)),
            middlewares: [],
            messageBus: $bus,
        );

        $async2Consumer = new Consumer(
            queue: 'foo.async2_queue',
            handlerRegistry: new ClassStringMapHandlerRegistry(ClassStringMap::create()->with(TransportTestEvent::class, $async2Handler)),
            middlewares: [],
            messageBus: $bus,
        );

        // Dispatch event, assert only sync message handlers
        $messageId = '1';
        $bus->dispatch(Envelope::wrap(new TransportTestEvent(), new MessageId($messageId)));

        self::assertSame([$messageId], $syncHandler->getHandledMessageIds());
        self::assertSame([], $async1Handler->getHandledMessageIds());
        self::assertSame([], $async2Handler->getHandledMessageIds());

        // Run async1 consumer
        $async1ConsumerCancel = $transportConsumer->runConsume($async1Consumer);
        EventLoop::delay(0.01, static function (string $id) use ($async1ConsumerCancel): void {
            $async1ConsumerCancel();
            EventLoop::cancel($id);
        });
        EventLoop::run();

        self::assertSame([$messageId], $syncHandler->getHandledMessageIds());
        self::assertSame([$messageId], $async1Handler->getHandledMessageIds());
        self::assertSame([], $async2Handler->getHandledMessageIds());

        // Run async1 consumer
        $async2ConsumerCancel = $transportConsumer->runConsume($async2Consumer);
        EventLoop::delay(0.01, static function (string $id) use ($async2ConsumerCancel): void {
            $async2ConsumerCancel();
            EventLoop::cancel($id);
        });
        EventLoop::run();

        self::assertSame([$messageId], $syncHandler->getHandledMessageIds());
        self::assertSame([$messageId], $async1Handler->getHandledMessageIds());
        self::assertSame([$messageId], $async2Handler->getHandledMessageIds());
    }

    /**
     * @return array{0: TransportPublisher, 1: TransportConsumer, 2: TransportSetup}
     */
    abstract protected function createTransport(): array;
}

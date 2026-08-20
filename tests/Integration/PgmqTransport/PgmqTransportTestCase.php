<?php

declare(strict_types=1);

namespace Trollbus\Tests\Integration\PgmqTransport;

use Revolt\EventLoop;
use Trollbus\MessageBus\Async\Consumer;
use Trollbus\MessageBus\Async\Delay\Delay;
use Trollbus\MessageBus\Async\Exchange\Exchange;
use Trollbus\MessageBus\Async\MessageType\OrigClassMessageTypeResolver;
use Trollbus\MessageBus\Async\ObjectNormalizer\PhpNativeSerializer;
use Trollbus\MessageBus\Async\TransportConsumer;
use Trollbus\MessageBus\Envelope;
use Trollbus\MessageBus\Handler\CallableHandler;
use Trollbus\MessageBus\HandlerRegistry\ClassStringMap;
use Trollbus\MessageBus\HandlerRegistry\ClassStringMapHandlerRegistry;
use Trollbus\MessageBus\MessageBus;
use Trollbus\MessageBus\MessageId\MessageId;
use Trollbus\MessageBus\Transaction\FakeTransactionProvider;
use Trollbus\PgmqTransport\DefaultPgmqEncoder;
use Trollbus\PgmqTransport\PgmqDriver;
use Trollbus\PgmqTransport\PgmqMessage;
use Trollbus\PgmqTransport\PgmqTransport;
use Trollbus\PgmqTransport\VisibilityTimeout\VisibilityTimeout;
use Trollbus\Tests\Unit\MessageBus\Async\TransportTestCase;

abstract class PgmqTransportTestCase extends TransportTestCase
{
    private const DEAD_LETTERS_QUEUE = 'dead_letters';

    protected ?PgmqDriver $driver = null;

    protected function setUp(): void
    {
        PgmqTool::reinitPostgresDb();
    }

    protected function createTransport(bool $archive = false): array
    {
        $this->driver = $this->createDriver();
        $objectSerializer = new PhpNativeSerializer();
        $encoder = new DefaultPgmqEncoder(
            normalizer: $objectSerializer,
            denormalizer: $objectSerializer,
            messageTypeResolver: new OrigClassMessageTypeResolver(),
        );

        $transport = new PgmqTransport(
            driver: $this->driver,
            encoder: $encoder,
            decoder: $encoder,
            transactionProvider: new FakeTransactionProvider(),
            archive: $archive,
        );

        return [$transport, $transport, $transport];
    }

    abstract protected function createDriver(): PgmqDriver;

    public function testSetupCreatesDeadLettersQueueAndBindsTopicsToQueues(): void
    {
        self::assertFalse($this->queueExists(self::DEAD_LETTERS_QUEUE));

        $transportSetup = $this->createTransport()[2];
        $transportSetup->setup(['topic.a' => ['queue_a', 'queue_b']]);

        // Dead letters queue was created.
        self::assertTrue($this->queueExists(self::DEAD_LETTERS_QUEUE));

        // Topic is bound to both queues.
        $this->driver->sendTopic('topic.a', '"probe"');

        self::assertSame('"probe"', $this->popFromQueue('queue_a')->value);
        self::assertNull($this->popFromQueue('queue_a'));

        self::assertSame('"probe"', $this->popFromQueue('queue_b')->value);
        self::assertNull($this->popFromQueue('queue_b'));
    }

    public function testSetupThrowsWhenQueueIsReservedDeadLettersName(): void
    {
        self::expectException(\LogicException::class);
        self::expectExceptionMessage('Reserved queue name "dead_letters". It used for deal letters.');

        $transportSetup = $this->createTransport()[2];
        $transportSetup->setup(['topic_a' => [self::DEAD_LETTERS_QUEUE]]);
    }

    public function testPublishThrowsWhenEnvelopeHasNoExchangeStamp(): void
    {
        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('Can not resolve topic.');

        $publisher = $this->createTransport()[0];
        $envelope = Envelope::wrap(new TransportTestCase\TransportTestEvent());
        $publisher->publish([$envelope]);
    }

    public function testPublishAppliesDelayStampAsVisibilityDelay(): void
    {
        $transportSetup = $this->createTransport()[2];
        $transportSetup->setup(['topic_a' => ['queue_a']]);

        $envelope = Envelope::wrap(
            new TransportTestCase\TransportTestEvent(),
            new Exchange('topic_a'),
            Delay::fromSeconds(60),
        );

        $transportSetup->publish([$envelope]);

        // Can not pop message, because delay is not ended
        self::assertNull($this->popFromQueue('queue_a'));

        // Get message from queue manually, because delayed message can not be got, using pgmq functions
        $conn = PgmqTool::connectToPostgresViaPdo();
        $stmt = $conn->prepare('SELECT * FROM pgmq.q_queue_a LIMIT 1');
        $stmt->execute();
        $data = $stmt->fetch();

        self::assertIsArray($data);
        $message = PgmqMessage::fromArray($data);
        self::assertSame(60, $message->visibilityTimeout->getTimestamp() - $message->enqueuedAt->getTimestamp());
    }

    public function testArchiveAfterConsume(): void
    {
        [$transportPublisher, $transportConsumer, $transportSetup] = $this->createTransport(true);
        $transportSetup->setup(['topic' => ['queue_a']]);
        $transportPublisher->publish([Envelope::wrap(new TransportTestCase\TransportTestEvent(), new Exchange('topic'), new MessageId('123'))]);

        $handled = false;
        $this->runConsumer($transportConsumer,'queue_a', function() use (&$handled) {
            $handled = true;
        });

        self::assertTrue($handled);

        // Assert archived pgmq message
        $conn = PgmqTool::connectToPostgresViaPdo();
        $stmt = $conn->prepare('SELECT * FROM pgmq.a_queue_a LIMIT 1');
        $stmt->execute();
        $data = $stmt->fetch();
        self::assertIsArray($data);
        $pgmqMessage = PgmqMessage::fromArray($data);
        $archivedEnvelope = unserialize(json_decode($pgmqMessage->value, true, flags: JSON_THROW_ON_ERROR));

        self::assertInstanceOf(Envelope::class, $archivedEnvelope);
        self::assertInstanceOf(TransportTestCase\TransportTestEvent::class, $archivedEnvelope->message);
        self::assertSame('123', $archivedEnvelope->getMessageId());
    }

    public function testAppliesVisibilityTimeout(): void
    {
        [$transportPublisher, $transportConsumer, $transportSetup] = $this->createTransport(true);
        $transportSetup->setup(['topic' => ['queue_a']]);
        $transportPublisher->publish([Envelope::wrap(new TransportTestCase\TransportTestEvent(), new Exchange('topic'), new VisibilityTimeout(600))]);

        $vt = null;
        $this->runConsumer($transportConsumer,'queue_a', function() use (&$vt) {
            $conn = PgmqTool::connectToPostgresViaPdo();
            $stmt = $conn->prepare('SELECT * FROM pgmq.q_queue_a');
            $stmt->execute();
            $pgmqMessage = PgmqMessage::fromArray((array) $stmt->fetch());

            $vt = $pgmqMessage->visibilityTimeout->getTimestamp() - $pgmqMessage->enqueuedAt->getTimestamp();
        });

        self::assertSame(600, $vt);
    }

    // todo retry success second

    // todo retry no success

    private function queueExists(string $queue): bool
    {
        $conn = PgmqTool::connectToPostgresViaPdo();
        $stmt = $conn->prepare('SELECT * FROM pgmq.metrics_all() WHERE queue_name = :queue');
        $stmt->execute(['queue' => $queue]);

        return false !== $stmt->fetchColumn();
    }

    private function popFromQueue(string $queue): ?PgmqMessage
    {
        $conn = PgmqTool::connectToPostgresViaPdo();
        $stmt = $conn->prepare('SELECT * FROM pgmq.pop(:queue)');
        $stmt->execute(['queue' => $queue]);
        $data = $stmt->fetch();

        if (false === $data) {
            return null;
        }

        return PgmqMessage::fromArray((array) $data);
    }

    /**
     * @param (\Closure(TransportTestCase\TransportTestEvent): void)|null $callback
     */
    private function runConsumer(TransportConsumer $transportConsumer, string $queue, ?\Closure $callback = null, float $delay = 0.01): void
    {
        $consumer = new Consumer(
            queue: $queue,
            handlerRegistry: new ClassStringMapHandlerRegistry(
                ClassStringMap::create()
                    ->with(TransportTestCase\TransportTestEvent::class, new CallableHandler('test', $callback ?? static fn() => null)),
            ),
            middlewares: [],
            messageBus: new MessageBus(),
        );

        $consumerCancel = $transportConsumer->runConsume($consumer);

        EventLoop::delay($delay, static function (string $id) use ($consumerCancel): void {
            $consumerCancel();
            EventLoop::cancel($id);
        });

        EventLoop::run();
    }
}

<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Trollbus\Message\Message;
use Trollbus\MessageBus\CreatedAt\CreatedAt;
use Trollbus\MessageBus\CreatedAt\CreatedAtMiddleware;
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

<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Trollbus\Message\Message;
use Trollbus\MessageBus\CreatedAt\CreatedAt;
use Trollbus\MessageBus\MessageId\CausationId;
use Trollbus\MessageBus\MessageId\CorrelationId;
use Trollbus\MessageBus\MessageId\MessageIdNotSet;
use Trollbus\MessageBus\ReadonlyMessageContext;
use Trollbus\MessageBus\Transaction\InTransaction;
use Trollbus\Tests\Psr\Log\InMemoryLogger;

/**
 * @psalm-require-extends TestCase
 */
trait MessageBusAssert
{
    /**
     * @param class-string<Message> $messageClass
     * @param non-empty-string $messageId
     * @param non-empty-string $correlationId
     * @param non-empty-string $causationId
     *
     * @throws MessageIdNotSet
     */
    final public static function assertMessageContext(
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
    final public static function assertLogLevels(array $logLevels, InMemoryLogger $logger): void
    {
        $actualLogLevels = array_map(static fn(array $log): string => $log[0], $logger->getLogs());

        self::assertSame($logLevels, $actualLogLevels);
    }
}

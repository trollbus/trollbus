<?php

declare(strict_types=1);

namespace Trollbus\DoctrinePersistence;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Psr\Clock\ClockInterface;
use Trollbus\Async\Delay\Delay;
use Trollbus\Async\Exchange;
use Trollbus\Message\Message;
use Trollbus\MessageBus\Envelope;
use Trollbus\MessageBus\MessageId\MessageIdNotSet;

final class DoctrineTransport
{
    private readonly Connection $connection;

    private readonly ConsumeIdGenerator $consumeIdGenerator;

    /** @var array<class-string<Message>, list<non-empty-string>> */
    private readonly array $messageClassesToQueues;

    private readonly ?ClockInterface $clock;

    /** @var non-empty-string */
    private readonly string $tableName;

    /**
     * @param non-empty-string $tableName
     * @param array<class-string<Message>, list<non-empty-string>> $messageClassesToQueues
     */
    public function __construct(
        Connection $connection,
        ConsumeIdGenerator $consumeIdGenerator,
        array $messageClassesToQueues,
        ?ClockInterface $clock = null,
        string $tableName = 'messages',
    ) {
        $this->connection = $connection;
        $this->consumeIdGenerator = $consumeIdGenerator;
        $this->messageClassesToQueues = $messageClassesToQueues;
        $this->clock = $clock;
        $this->tableName = $tableName;
    }

    public function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable($this->tableName);
        $table->addColumn('message_id', Types::TEXT);
        $table->addColumn('queue', Types::TEXT);
        $table->addColumn('exchange', Types::TEXT);
        $table->addColumn('envelope', Types::BINARY);
        $table->addColumn('published_at', Types::DATETIMETZ_IMMUTABLE);
        $table->addColumn('consume_id', Types::TEXT)->setNotnull(false);
        $table->addColumn('consumed_at', Types::DATETIMETZ_IMMUTABLE)->setNotnull(false);
        $table->setPrimaryKey(['message_id', 'queue']);
    }

    /**
     * @throws MessageIdNotSet
     * @throws Exception
     */
    public function publish(Envelope $envelope): void
    {
        $messageId = $envelope->getMessageId();
        $queues = $this->messageClassesToQueues[$envelope->getMessageClass()] ?? [];
        $exchange = $envelope->getStamp(Exchange::class)?->exchange ?? throw new \LogicException('No exchange stamp.');

        $publishedAt = $this->now();

        if ($envelope->hasStamp(Delay::class)) {
            $milliseconds = $envelope->getStamp(Delay::class)?->milliseconds ?? 0;
            $publishedAt = $publishedAt->modify("+{$milliseconds} ms");
        }

        foreach ($queues as $queue) {
            $this->connection->executeStatement(
                <<<SQL
                    insert into {$this->tableName} (message_id, queue, exchange, envelope, published_at, consume_id, consumed_at)
                    values (?, ?, ?, ?, ?, ?)
                    SQL,
                [$messageId, $queue, $exchange, serialize($envelope), $publishedAt, null, null],
                [3 => Types::BINARY, 4 => Types::DATETIMETZ_IMMUTABLE],
            );
        }
    }

    /**
     * @param non-empty-string $queue
     * @throws Exception
     */
    public function get(string $queue): ?Envelope
    {
        // Get first non-consumed message
        $result = $this->connection
            ->createQueryBuilder()
            ->select('message_id', 'envelope')
            ->from($this->tableName)
            ->andWhere('queue = :queue')
            ->andWhere('published_at < :published_at')
            ->andWhere('consume_id is null')
            ->setParameter('queue', $queue)
            ->setParameter('published_at', $this->now(), Types::DATETIMETZ_IMMUTABLE)
            ->orderBy('published_at', 'ASC')
            ->setMaxResults(1)
            ->executeQuery();

        /** @var array{message_id: non-empty-string, envelope: resource|string}|false $data */
        $data = $result->fetchAssociative();

        if (false === $data) {
            return null;
        }

        // Mark message as consumed
        $consumeId = $this->consumeIdGenerator->generate();
        $affectedRows = (int) $this->connection->executeStatement(
            <<<SQL
                update {$this->tableName}
                set
                    consume_id = ?,
                    consumed_at = ?
                where
                    message_id = ? and
                    queue = ? and
                    consume_id is null
                SQL,
            [$consumeId, $this->now(), $data['message_id'], $queue],
            [1 => Types::DATETIMETZ_IMMUTABLE],
        );

        if (0 === $affectedRows) {
            return null;
        }

        /** @psalm-suppress MixedAssignment */
        if (\is_resource($data['envelope'])) {
            $envelope = unserialize(stream_get_contents($data['envelope']));
        } else {
            $envelope = unserialize($data['envelope']);
        }

        if ($envelope instanceof Envelope) {
            return $envelope;
        }

        $this->reject($data['message_id'], $queue);

        return null;
    }

    /**
     * @param non-empty-string $messageId
     * @param non-empty-string $queue
     * @throws Exception
     */
    public function ack(string $messageId, string $queue): void
    {
        $this->connection->executeQuery(
            <<<SQL
                    delete from {$this->tableName}
                    where
                        message_id = ? and
                        queue = ? and
                        consume_id is null
                SQL,
            [$messageId, $queue],
        );
    }

    /**
     * @throws Exception
     */
    public function reject(string $messageId, string $queue): void
    {
        // Move message to end of queue
        $publishedAt = $this->now();

        $this->connection->executeQuery(
            <<<SQL
                    update {$this->tableName}
                    set
                        published_at = ?,
                        consume_id = null,
                        consumed_at = null
                    where
                        message_id = ? and
                        queue = ?
                SQL,
            [$publishedAt, $messageId, $queue],
            [0 => Types::DATETIMETZ_IMMUTABLE],
        );
    }

    private function now(): \DateTimeImmutable
    {
        return $this->clock?->now() ?? new \DateTimeImmutable();
    }
}

<?php

declare(strict_types=1);

namespace Trollbus\PgmqTransport\Driver;

use Revolt\EventLoop;
use Trollbus\PgmqTransport\PgmqDriver;
use Trollbus\PgmqTransport\PgmqMessage;

final class PdoDriver implements PgmqDriver
{
    /** @var array<non-empty-string, \Closure(PgmqMessage): void> */
    private array $consumers = [];

    private string $listenId = '';

    public function __construct(
        private readonly \PDO $conn,
    ) {
        $driver = $this->conn->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ('pgsql' !== $driver) {
            throw new \LogicException(\sprintf('Invalid db driver. Expected "pgsql", actual "%s".', $driver));
        }
    }

    public function createQueue(string $queue): void
    {
        $this->conn->prepare('SELECT pgmq.create(:queue)')->execute(['queue' => $queue]);
        $this->conn->prepare('SELECT pgmq.enable_notify_insert(:queue);')->execute(['queue' => $queue]);
    }

    public function dropQueue(string $queue): void
    {
        $this->conn->prepare('SELECT pgmq.disable_notify_insert(:queue)')->execute(['queue' => $queue]);
        $this->conn->prepare('SELECT pgmq.drop_queue(:queue)')->execute(['queue' => $queue]);
    }

    public function send(string $queue, string $message, ?string $headers = null, int $delay = 0): void
    {
        $this->conn
            ->prepare('SELECT pgmq.send(:queue, :message, :headers, :delay)')
            ->execute([
                'queue' => $queue,
                'message' => $message,
                'headers' => $headers,
                'delay' => $delay,
            ]);
    }

    public function setVisibilityTimeout(string $queue, int $msgId, int $visibilityTimeout): void
    {
        $this->conn
            ->prepare('SELECT * FROM pgmq.set_vt(:queue, :msgId, :vt);')
            ->execute([
                'queue' => $queue,
                'msgId' => $msgId,
                'vt' => $visibilityTimeout,
            ]);
    }

    public function consume(string $queue, \Closure $callback): \Closure
    {
        if (isset($this->consumers[$queue])) {
            throw new \LogicException(\sprintf('Consumer for queue "%s" already exists.', $queue));
        }

        $first = [] === $this->consumers;
        $this->consumers[$queue] = $callback;

        // $deferId triggers an immediate one-time processing of the queue
        // to handle messages that arrived before the listener was set up.
        $deferId = EventLoop::defer(function () use ($queue): void {
            $this->listenQueue($queue);
            $this->handleAllMessagesInQueue($queue);
        });

        // $pollId is required because LISTEN/NOTIFY works only on INSERT.
        // It does not fire on retry (UPDATE vt) or on messages with delay.
        // Polling every 1s ensures delayed/retried messages are eventually processed.
        $pollId = EventLoop::repeat(1, fn() => $this->handleAllMessagesInQueue($queue));

        // $this->listenId is a global listener, not per-queue.
        // pgsqlGetNotify() returns notifications for all LISTEN channels,
        // so one instance is sufficient for processing all queues.
        if ($first) {
            $this->listenId = EventLoop::repeat(0.1, function (): void {
                $queues = [];

                while (false !== ($notify = @$this->conn->pgsqlGetNotify(\PDO::FETCH_ASSOC))) {
                    $channel = $notify['message'];
                    $queue = self::channelToQueue($channel);

                    if (!in_array($queue, $queues, true)) {
                        $queues[] = $queue;
                    }
                }

                foreach ($queues as $queue) {
                    $this->handleAllMessagesInQueue($queue);
                }
            });
        }

        return function () use ($queue, $deferId, $pollId): void {
            unset($this->consumers[$queue]);
            EventLoop::cancel($deferId);
            EventLoop::cancel($pollId);
            $this->unlistenQueue($queue);

            if ([] === $this->consumers) {
                EventLoop::cancel($this->listenId);
            }
        };
    }

    public function bindTopic(string $pattern, string $queue): void
    {
        $this->conn->prepare('SELECT pgmq.bind_topic(:pattern, :queue)')->execute(['pattern' => $pattern, 'queue' => $queue]);
    }

    public function unbindTopic(string $pattern, string $queue): void
    {
        $this->conn->prepare('SELECT pgmq.unbind_topic(:pattern, :queue)')->execute(['pattern' => $pattern, 'queue' => $queue]);
    }

    public function sendTopic(string $pattern, string $message, ?string $headers = null, int $delay = 0): void
    {
        $this->conn
            ->prepare('select pgmq.send_topic(:pattern, :message, :headers, :delay)')
            ->execute([
                'pattern' => $pattern,
                'message' => $message,
                'headers' => $headers,
                'delay' => $delay,
            ]);
    }

    public function ack(string $queue, int $msgId, bool $archive): void
    {
        if ($archive) {
            $this->conn->prepare('SELECT pgmq.archive(:queue, :msg_id::bigint)')
                ->execute([
                    'queue' => $queue,
                    'msg_id' => $msgId,
                ]);
        } else {
            $this->conn->prepare('SELECT pgmq.delete(:queue, :msg_id::bigint)')
                ->execute([
                    'queue' => $queue,
                    'msg_id' => $msgId,
                ]);
        }
    }

    /**
     * @param non-empty-string $queue
     */
    private function handleAllMessagesInQueue(string $queue): void
    {
        while (true) {
            $callback = $this->consumers[$queue] ?? null;

            // If no callback, then consumer was canceled
            if (null === $callback) {
                return;
            }

            $message = $this->readNextMessageFromQueue($queue);

            // Skip, if all messages was handled
            if (null === $message) {
                return;
            }

            $callback($message);
        }
    }

    private function readNextMessageFromQueue(string $queue): ?PgmqMessage
    {
        $stmt = $this->conn->prepare(
            <<<'SQL'
                    SELECT * FROM pgmq.read(
                        queue_name => :queue,
                        vt         => 30, -- Visibility Timeout, 30s by default
                        qty        => 1
                    )        
                SQL,
        );
        $stmt->execute(['queue' => $queue]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (false === $result) {
            return null;
        }

        return PgmqMessage::fromArray($result);
    }

    private function listenQueue(string $queue): void
    {
        $this->conn->exec(\sprintf('LISTEN "%s"', self::queueToChannel($queue)));
    }

    private function unlistenQueue(string $queue): void
    {
        $this->conn->exec(\sprintf('UNLISTEN "%s"', self::queueToChannel($queue)));
    }

    /**
     * @param non-empty-string $queue
     *
     * @return non-empty-string
     */
    private static function queueToChannel(string $queue): string
    {
        return "pgmq.q_{$queue}.INSERT";
    }

    /**
     * @param non-empty-string $channel
     *
     * @return non-empty-string
     */
    private static function channelToQueue(string $channel): string
    {
        preg_match('/^pgmq\.q_(.+)\.INSERT$/', $channel, $matches);

        return $matches[1];
    }
}

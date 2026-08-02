<?php

declare(strict_types=1);

namespace Trollbus\PgmqTransport\Driver;

use Trollbus\PgmqTransport\PgmqDriver;

final class PdoDriver implements PgmqDriver
{
    private ?\PDO $conn;

    public function __construct(
        \PDO $conn,
    ) {
        // todo check driver postgresql
        // todo check, that pgmq is installed
        $this->conn = $conn;
    }

    public function createQueue(string $queue): void
    {
        $this->conn()->prepare("SELECT pgmq.create(':queue')")->execute(['queue' => $queue]);
    }

    public function dropQueue(string $queue): void
    {
        $this->conn()->prepare("SELECT pgmq.drop_queue(':queue')")->execute(['queue' => $queue]);
    }

    public function consume(string $queue, \Closure $callback): \Closure
    {
        // TODO: Implement consume() method.
    }

    public function bindTopic(string $pattern, string $queue): void
    {
        $this->conn()->prepare("SELECT pgmq.bind_topic(':pattern', ':queue')")->execute(['pattern' => $pattern, 'queue' => $queue]);
        $this->conn()->prepare("SELECT pgmq.enable_notify_insert(':queue');")->execute(['queue' => $queue]);
    }

    public function unbindTopic(string $pattern, string $queue): void
    {
        $this->conn()->prepare("SELECT pgmq.disable_notify_insert(':queue');")->execute(['queue' => $queue]);
        $this->conn()->prepare("SELECT pgmq.unbind_topic(':pattern', ':queue')")->execute(['pattern' => $pattern, 'queue' => $queue]);
    }

    public function sendTopic(string $pattern, string $message, ?string $headers = null, int $delay = 0): void
    {
        $this->conn()
            ->prepare("select pgmq.send_topic(':pattern', ':message', ':headers', :delay)")
            ->execute([
                'pattern' => $pattern,
                'message' => $message,
                'headers' => $headers,
                'delay' => $delay,
            ]);
    }

    public function disconnect(): void
    {
        $this->conn = null;
    }

    private function conn(): \PDO
    {
        return $this->conn ?? throw new \RuntimeException('Cannot execute query: PDO connection has been closed.');
    }
}

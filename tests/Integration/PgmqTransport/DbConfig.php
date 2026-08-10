<?php

declare(strict_types=1);

namespace Trollbus\Tests\Integration\PgmqTransport;

final class DbConfig
{
    /**
     * @param non-empty-string $host
     * @param int<0, 65535> $port
     * @param non-empty-string $username
     * @param non-empty-string $password
     * @param non-empty-string $dbname
     */
    public function __construct(
        public readonly string $host,
        public readonly int $port,
        public readonly string $username,
        #[\SensitiveParameter]
        public readonly string $password,
        public readonly string $dbname,
    ) {}

    public static function fromEnv(): self
    {
        $host = $_SERVER['DB_HOST'] ?? null;
        $port = isset($_SERVER['DB_PORT']) ? (int) $_SERVER['DB_PORT'] : null;
        $username = $_SERVER['DB_USER'] ?? null;
        $password = $_SERVER['DB_PASSWORD'] ?? null;
        $dbname = $_SERVER['DB_NAME'] ?? null;

        \assert(\is_string($host) && '' !== $host);
        \assert(\is_int($port) && $port >= 0 && $port <= 65_535);
        \assert(\is_string($username) && '' !== $username);
        \assert(\is_string($password) && '' !== $password);
        \assert(\is_string($dbname) && '' !== $dbname);

        return new self(
            host: $host,
            port: $port,
            username: $username,
            password: $password,
            dbname: $dbname,
        );
    }
}

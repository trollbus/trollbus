<?php

declare(strict_types=1);

namespace Trollbus\Tests\Integration\PgmqTransport;

final class PgmqTool
{
    public static function reinitPostgresDb(?DbConfig $config = null): void
    {
        $config ??= DbConfig::fromEnv();

        if ('postgres' === $config->dbname) {
            throw new \RuntimeException('Database name "postgres" is reserved.');
        }

        // Recreate db
        $configForRecreateDb = new DbConfig(
            host: $config->host,
            port: $config->port,
            username: $config->username,
            password: $config->password,
            dbname: 'postgres',
        );
        $conn = self::connectToPostgresViaPdo($configForRecreateDb);
        $conn->exec("DROP DATABASE IF EXISTS {$config->dbname}");

        try {
            $conn->exec("CREATE DATABASE {$config->dbname}");
        } catch (\PDOException) {
        }

        // Create extension
        $conn = self::connectToPostgresViaPdo($config);
        $conn->exec('CREATE EXTENSION IF NOT EXISTS pgmq');
    }

    public static function connectToPostgresViaPdo(?DbConfig $config = null): \PDO
    {
        $config ??= DbConfig::fromEnv();

        $dsn = "pgsql:host={$config->host};port={$config->port};dbname={$config->dbname};";
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return new \PDO(dsn: $dsn, username: $config->username, password: $config->password, options: $options);
    }
}

<?php

declare(strict_types=1);

namespace Trollbus\Tests\Integration\PgmqTransport;

use PHPUnit\Framework\TestCase;

final class PdoConnectTest extends TestCase
{
    public function test(): void
    {
        PgmqTool::reinitPostgresDb();
        $conn = PgmqTool::connectToPostgresViaPdo();

        self::assertSame(1, $conn->query('SELECT 1')->fetchColumn());
    }
}

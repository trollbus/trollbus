<?php

declare(strict_types=1);

namespace Trollbus\Tests\Integration\PgmqTransport;

use Trollbus\PgmqTransport\Driver\PdoDriver;
use Trollbus\PgmqTransport\PgmqDriver;

final class PgmqTransportWithPdoDriverTest extends PgmqTransportTestCase
{
    protected function createDriver(): PgmqDriver
    {
        $conn = PgmqTool::connectToPostgresViaPdo();

        return new PdoDriver($conn);
    }
}
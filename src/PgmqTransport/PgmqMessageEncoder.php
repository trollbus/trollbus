<?php

declare(strict_types=1);

namespace Trollbus\PgmqTransport;

use Trollbus\MessageBus\Envelope;

interface PgmqMessageEncoder
{
    public function encode(Envelope $envelope): PgmqSendMessage;
}

<?php

declare(strict_types=1);

namespace Trollbus\PgmqTransport;

use Trollbus\MessageBus\Envelope;

interface PgmqMessageDecoder
{
    public function decode(PgmqMessage $pgmqMessage): Envelope;
}

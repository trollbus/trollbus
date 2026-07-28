<?php

declare(strict_types=1);

namespace App\Middleware\Attribute\MessageTrack;

use Trollbus\MessageBus\Stamp;

final class MessageTrack implements Stamp
{
    /** @var list<non-empty-string> */
    public private(set) array $tracks = [];

    public function addTrack(string $track): void
    {
        $this->tracks[] = $track;
    }
}

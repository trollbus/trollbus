<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\MessageId;

use Trollbus\MessageBus\Stamp;

final class CausationId implements Stamp
{
    /** @var non-empty-string|null */
    public readonly ?string $causationId;

    /**
     * @param non-empty-string|null $causationId
     */
    public function __construct(?string $causationId)
    {
        $this->causationId = $causationId;
    }
}

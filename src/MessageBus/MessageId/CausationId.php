<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus\MessageId;

use Kenny1911\SisyphBus\MessageBus\Stamp;

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

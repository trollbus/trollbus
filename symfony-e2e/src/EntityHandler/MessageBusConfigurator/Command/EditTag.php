<?php

declare(strict_types=1);

namespace App\EntityHandler\MessageBusConfigurator\Command;

use Symfony\Component\Uid\Uuid;
use Trollbus\Message\Message;

/**
 * @implements Message<void>
 */
final readonly class EditTag implements Message
{
    public function __construct(
        public Uuid $id,
        public string $name,
    ) {}
}

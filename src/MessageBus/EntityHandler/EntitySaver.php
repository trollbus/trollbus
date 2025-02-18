<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\EntityHandler;

interface EntitySaver
{
    public function save(object $entity): void;
}

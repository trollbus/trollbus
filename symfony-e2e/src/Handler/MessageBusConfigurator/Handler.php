<?php

declare(strict_types=1);

namespace App\Handler\MessageBusConfigurator;

final readonly class Handler
{
    public function handleSomeCommand(SomeCommand $command): void {}
}

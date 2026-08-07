<?php

declare(strict_types=1);

namespace App\Handler\MessageBusConfigurator;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Trollbus\TrollbusBundle\DependencyInjection\MessageBusConfigurator;

return static function (ContainerConfigurator $di): void {
    $di->services()
        ->set(Handler::class);

    MessageBusConfigurator::create($di)
        ->callableHandler(
            message: SomeCommand::class,
            service: Handler::class,
            method: 'handleSomeCommand',
        );
};

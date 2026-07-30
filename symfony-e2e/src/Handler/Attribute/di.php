<?php

declare(strict_types=1);

namespace App\Handler\Attribute;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $di): void {
    $di->services()
        ->set(AttributeBasedHandler::class)
            ->autoconfigure()
            ->public();
};

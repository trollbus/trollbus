<?php

declare(strict_types=1);

namespace App\Middleware\Attribute;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $di): void {
    $di->services()
        ->set(AttributeBasedHandlerAndMiddleware::class)
            ->autoconfigure()
            ->public();
};

<?php

declare(strict_types=1);

namespace App\EntityHandler\Attribute\EventListener;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $di): void {
    $di->services()
        ->set(PostListener::class)
            ->public()
            ->autoconfigure();
};

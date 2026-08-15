<?php

declare(strict_types=1);

namespace App\Middleware\EnvelopeCollector;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Trollbus\TrollbusBundle\DependencyInjection\MessageBusConfiguration;

return static function (ContainerConfigurator $di): void {
    $di->services()
        ->set(EnvelopeCollector::class)
            ->public()
            ->tag(MessageBusConfiguration::MIDDLEWARE_TAG, ['priority' => 5_000]);
};

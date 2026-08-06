<?php

declare(strict_types=1);

namespace App\EntityHandler\MessageBusConfigurator;

use App\EntityHandler\MessageBusConfigurator\Command\CreateTag;
use App\EntityHandler\MessageBusConfigurator\Command\EditTag;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Trollbus\TrollbusBundle\DependencyInjection\MessageBusConfigurator;

return static function (ContainerConfigurator $di): void {
    MessageBusConfigurator::create($di)
        ->entityFactoryHandler(
            message: CreateTag::class,
            entityClass: Tag::class,
            handlerMethod: 'create',
        )->entityHandler(
            message: EditTag::class,
            entityClass: Tag::class,
            handlerMethod: 'edit',
            findBy: ['id' => 'id'],
        );
};

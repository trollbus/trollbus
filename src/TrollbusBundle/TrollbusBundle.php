<?php

declare(strict_types=1);

namespace Trollbus\TrollbusBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Trollbus\MessageBus\MessageBus;
use Trollbus\TrollbusBundle\DependencyInjection\CompilerPass\HandlerRegistryPass;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

/**
 * @psalm-type Config = array{
 *     service_prefix: non-empty-string
 * }
 */
final class TrollbusBundle extends AbstractBundle
{
    #[\Override]
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $serviceName = 'message_bus';
        // $serviceName = 'trollbus';

        $builder->addCompilerPass(new HandlerRegistryPass($serviceName));

        $container->services()
            ->set(MessageBus::class)
                ->args([
                    service($serviceName . '.handler_registry'),
                    tagged_iterator($serviceName . '.handler_middleware'),
                ]);
    }
}

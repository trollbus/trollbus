<?php

namespace Trollbus\Tests\TrollbusBundle;

use Psr\Container\ContainerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use PHPUnit\Framework\TestCase;

final class TrollbusBundleTest extends TestCase
{
    /**
     * @param callable(ContainerConfigurator):void|null $configure
     */
    private function createContainer(?callable $configure = null): ContainerInterface
    {
        $container = new ContainerBuilder();
        $instanceof = [];
        $configurator = new ContainerConfigurator(
            container: $container,
            loader: new PhpFileLoader(
                container: $container,
                locator: new FileLocator(__DIR__),
            ),
            instanceof: $instanceof,
            path: __FILE__,
            file: __FILE__,
        );

        if (null !== $configure) {
            $configure($configurator);
        }

        $container->compile();

        return $container;
    }
}

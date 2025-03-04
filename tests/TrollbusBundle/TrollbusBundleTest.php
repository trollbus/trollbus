<?php

declare(strict_types=1);

namespace Trollbus\Tests\TrollbusBundle;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Trollbus\MessageBus\MessageBus;
use Trollbus\Tests\DoctrineORMBridge\ManagerRegistry;
use Trollbus\Tests\MessageBus\MessageId\SequenceMessageIdGenerator;
use Trollbus\Tests\Psr\Clock\FakeClock;
use Trollbus\Tests\Psr\Log\InMemoryLogger;
use Trollbus\TrollbusBundle\TrollbusBundle;
use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;

final class TrollbusBundleTest extends TestCase
{
    public function test(): void
    {
        $container = $this->createContainerWithAllEnabledConfigs();
        $trollbus = $container->get('trollbus');

        $this->assertInstanceOf(MessageBus::class, $trollbus);
        dump($trollbus);
    }
    /**
     * @param callable(ContainerConfigurator):void|null $configure
     */
    private function createContainerWithAllEnabledConfigs(?callable $configure = null): ContainerInterface
    {
        return $this->createContainer(static function (ContainerConfigurator $di) use ($configure): void {
            $di->services()
                ->set('doctrine', ManagerRegistry::class)
                    ->args([
                        __DIR__,
                    ])
                    ->public()
                ->set('clock', FakeClock::class)
                    ->args([
                        inline_service(\DateTimeImmutable::class)
                            ->args([
                                '2025-01-01 00:00:00',
                            ]),
                    ])
                    ->public()
                ->set('logger', InMemoryLogger::class)
                    ->public()
                ->set(SequenceMessageIdGenerator::class)
                    ->public();

            $di->extension('trollbus', [
                'created_at' => [
                    'enabled' => true,
                    'clock' => 'clock',
                ],
                'logger' => [
                    'enabled' => true,
                ],
                'message_id' => [
                    'enabled' => true,
                    'generator' => SequenceMessageIdGenerator::class,
                ],
                'transaction' => [
                    'enabled' => true,
                ],
                'entity_handler' => [
                    'enabled' => true,
                ],
                'doctrine_orm_bridge' => [
                    'enabled' => true,
                ],
            ]);

            if (null !== $configure) {
                $configure($di);
            }
        });
    }

    /**
     * @param callable(ContainerConfigurator):void|null $configure
     */
    private function createContainer(?callable $configure = null): ContainerInterface
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', __DIR__ . '/../../var/TrollbusBundle/cache');

        $bundle = new TrollbusBundle();
        $bundle->build($container);
        $container->registerExtension($bundle->getContainerExtension() ?? throw new \LogicException('No bundle extension.'));

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

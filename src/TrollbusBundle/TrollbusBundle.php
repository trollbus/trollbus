<?php

declare(strict_types=1);

namespace Trollbus\TrollbusBundle;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Trollbus\MessageBus\CreatedAt\CreatedAtMiddleware;
use Trollbus\MessageBus\Logging\LogMiddleware;
use Trollbus\MessageBus\MessageBus;
use Trollbus\MessageBus\MessageId\CausationIdMiddleware;
use Trollbus\MessageBus\MessageId\CorrelationIdMiddleware;
use Trollbus\MessageBus\MessageId\MessageIdMiddleware;
use Trollbus\MessageBus\MessageId\RandomMessageIdGenerator;
use Trollbus\TrollbusBundle\DependencyInjection\CompilerPass\HandlerRegistryPass;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

/**
 * @psalm-type Config = array{
 *     created_at: array{
 *         enabled: bool,
 *         clock: non-empty-string|null
 *     },
 *     logger: array{
 *         enabled: bool,
 *         logger: non-empty-string
 *     },
 *     message_id: array{
 *         enabled: bool,
 *         generator: non-empty-string|null
 *     },
 *     transaction: array{
 *         enabled: bool,
 *         transaction_provider: non-empty-string|null
 *     },
 *     entity_handler: array{
 *         enabled: bool,
 *         entity_filter: non-empty-string|null,
 *         entity_saver: non-empty-string|null,
 *         criteria_resolver: non-empty-string|null
 *     },
 *     doctrine_orm_bridge: array{
 *         enabled: bool,
 *         manager_registry: non-empty-string,
 *         manager: non-empty-string|null
 *     }
 * }
 */
final class TrollbusBundle extends AbstractBundle
{
    protected string $extensionAlias = 'trollbus';

    #[\Override]
    public function configure(DefinitionConfigurator $definition): void
    {
        $config = $definition->rootNode()->children();

        $this->configureCreatedAt($config);
        $this->configureLogger($config);
        $this->configureMessageId($config);
        $this->configureTransaction($config);
        $this->configureEntityHandler($config);
        $this->configureDoctrineOrmBridge($config);
    }

    /**
     * @psalm-param Config $config
     */
    #[\Override]
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        /** @var non-empty-string $prefix */
        $prefix = $this->extensionAlias;
        $services = $container->services();

        $this->loadCreatedAt($config, $services);
        $this->loadLogger($config, $services);
        $this->loadMessageId($config, $services);
        $this->loadTransaction($config, $services);
        $this->loadEntityHandler($config, $services);
        $this->loadDoctrineOrmBridge($config, $services);

        $builder->addCompilerPass(new HandlerRegistryPass($prefix));

        $container->services()
            ->set(MessageBus::class)
                ->args([
                    service($prefix . '.handler_registry'),
                    tagged_iterator($prefix . '.middleware'),
                ]);
    }

    private function configureCreatedAt(NodeBuilder $config): void
    {
        $config
            ->arrayNode('created_at')
                ->canBeDisabled()

                ->children()
                    ->stringNode('clock')
                        ->cannotBeEmpty()->beforeNormalization()->ifTrue()->then()->end()
                        ->defaultNull();
    }

    /**
     * @psalm-param Config $config
     */
    private function loadCreatedAt(array $config, ServicesConfigurator $services): void
    {
        if ($config['created_at']['enabled']) {
            $services
                ->set(CreatedAtMiddleware::class)
                    ->args([
                        isset($config['created_at']['clock']) ? service($config['created_at']['clock']) : null,
                    ])
                    ->tag($this->extensionAlias . '.middleware', ['priority' => 1000]);
        }
    }

    private function configureLogger(NodeBuilder $config): void
    {
        $config
            ->arrayNode('logger')
                ->canBeEnabled()
                ->children()
                    ->stringNode('logger')
                        ->cannotBeEmpty()
                        ->defaultValue('logger');
    }

    /**
     * @psalm-param Config $config
     */
    private function loadLogger(array $config, ServicesConfigurator $services): void
    {
        if ($config['logger']['enabled']) {
            $services
                ->set(LogMiddleware::class)
                    ->args([service($config['logger']['logger'])])
                    ->tag($this->extensionAlias . '.middleware', ['priority' => 900]);
        }
    }

    private function configureMessageId(NodeBuilder $config): void
    {
        $config
            ->arrayNode('message_id')
                ->canBeDisabled()
                ->children()
                    ->stringNode('generator')
                        ->cannotBeEmpty()
                        ->defaultNull();
    }

    /**
     * @psalm-param Config $config
     */
    private function loadMessageId(array $config, ServicesConfigurator $services): void
    {
        if ($config['message_id']['enabled']) {
            if (null === $config['message_id']['generator']) {
                $services->set(RandomMessageIdGenerator::class);
                $generator = service(RandomMessageIdGenerator::class);
            } else {
                $generator = service($config['message_id']['generator']);
            }

            $services
                ->set(MessageIdMiddleware::class)
                    ->args([
                        $generator,
                    ])
                    ->tag($this->extensionAlias . '.middleware', ['priority' => 810])

                ->set(CorrelationIdMiddleware::class)
                    ->tag($this->extensionAlias . '.middleware', ['priority' => 800])

                ->set(CausationIdMiddleware::class)
                    ->tag($this->extensionAlias . '.middleware', ['priority' => 800]);
        }
    }

    private function configureTransaction(NodeBuilder $config): void
    {
        $config
            ->arrayNode('transaction')
            ->canBeEnabled()
                ->children()
                    ->stringNode('transaction_provider')
                        ->cannotBeEmpty()
                        ->defaultNull();
    }

    /**
     * @psalm-param Config $config
     */
    private function loadTransaction(array $config, ServicesConfigurator $services): void
    {
        // todo
    }

    private function configureEntityHandler(NodeBuilder $config): void
    {
        $config
            ->arrayNode('entity_handler')
            ->canBeEnabled()
            ->children()
                ->stringNode('entity_filter')
                    ->cannotBeEmpty()
                    ->defaultNull()
                    ->end()
                ->stringNode('entity_saver')
                    ->cannotBeEmpty()
                    ->defaultNull()
                    ->end()
                ->stringNode('criteria_resolver')
                    ->cannotBeEmpty()
                    ->defaultNull();
    }

    /**
     * @psalm-param Config $config
     */
    private function loadEntityHandler(array $config, ServicesConfigurator $services): void
    {
        // todo
    }

    private function configureDoctrineOrmBridge(NodeBuilder $config): void
    {
        $config
            ->arrayNode('doctrine_orm_bridge')
                ->canBeEnabled()
                ->children()
                    ->stringNode('manager_registry')
                        ->cannotBeEmpty()
                        ->defaultValue('doctrine')
                        ->end()
                    ->stringNode('manager')
                        ->cannotBeEmpty()
                        ->defaultNull();
    }

    /**
     * @psalm-param Config $config
     */
    private function loadDoctrineOrmBridge(array $config, ServicesConfigurator $services): void
    {
        // todo
    }
}

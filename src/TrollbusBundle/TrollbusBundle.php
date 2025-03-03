<?php

declare(strict_types=1);

namespace Trollbus\TrollbusBundle;

use Composer\InstalledVersions;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Trollbus\DoctrineORMBridge\EntityHandler\DoctrineEntityFinder;
use Trollbus\DoctrineORMBridge\EntityHandler\DoctrineEntitySaver;
use Trollbus\DoctrineORMBridge\Flusher\FlusherMiddleware;
use Trollbus\DoctrineORMBridge\Transaction\DoctrineTransactionProvider;
use Trollbus\MessageBus\CreatedAt\CreatedAtMiddleware;
use Trollbus\MessageBus\EntityHandler\PropertyCriteriaResolver;
use Trollbus\MessageBus\Logging\LogMiddleware;
use Trollbus\MessageBus\MessageBus;
use Trollbus\MessageBus\MessageId\CausationIdMiddleware;
use Trollbus\MessageBus\MessageId\CorrelationIdMiddleware;
use Trollbus\MessageBus\MessageId\MessageIdMiddleware;
use Trollbus\MessageBus\MessageId\RandomMessageIdGenerator;
use Trollbus\MessageBus\Transaction\WrapInTransactionMiddleware;
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
 *         entity_finder: non-empty-string|null,
 *         entity_saver: non-empty-string|null,
 *         criteria_resolver: non-empty-string|null
 *     },
 *     doctrine_orm_bridge: array{
 *         enabled: bool,
 *         manager_registry: non-empty-string,
 *         manager: non-empty-string|null,
 *         entity_saver_flush: bool,
 *         flusher: bool
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
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    #[\Override]
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();

        $this->loadCreatedAt($config, $services);
        $this->loadLogger($config, $services);
        $this->loadMessageId($config, $services);
        $this->loadDoctrineOrmBridge($config, $services);
        $this->loadTransaction($config, $services);
        $this->loadEntityHandler($config, $services);

        $builder->addCompilerPass(new HandlerRegistryPass('trollbus'));

        $container
            ->services()
            ->set(MessageBus::class)
                ->args([
                    service('trollbus.handler_registry'),
                    tagged_iterator('trollbus.middleware'),
                ])
            ->alias('trollbus', MessageBus::class);
    }

    private function configureCreatedAt(NodeBuilder $config): void
    {
        $config
            ->arrayNode('created_at')
                ->canBeDisabled()

                ->children()
                    ->stringNode('clock')
                        ->cannotBeEmpty()
                        ->defaultNull();
    }

    /**
     * @psalm-param Config $config
     */
    private function loadCreatedAt(array $config, ServicesConfigurator $services): void
    {
        if (false === $config['created_at']['enabled']) {
            return;
        }

        $services
            ->set(CreatedAtMiddleware::class)
                ->args([
                    isset($config['created_at']['clock']) ? service($config['created_at']['clock']) : null,
                ])
                ->tag('trollbus.middleware', ['priority' => 1000]);
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
        if (false === $config['logger']['enabled']) {
            return;
        }

        $services
            ->set(LogMiddleware::class)
                ->args([
                    service($config['logger']['logger']),
                ])
                ->tag('trollbus.middleware', ['priority' => 900]);
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
        if (false === $config['message_id']['enabled']) {
            return;
        }

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
                ->tag('trollbus.middleware', ['priority' => 810])

            ->set(CorrelationIdMiddleware::class)
                ->tag('trollbus.middleware', ['priority' => 800])

            ->set(CausationIdMiddleware::class)
                ->tag('trollbus.middleware', ['priority' => 800]);
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
        if (false === $config['transaction']['enabled']) {
            return;
        }

        if (null === $config['transaction']['transaction_provider']) {
            throw new LogicException('Transaction provider not defined. Please, define config "trollbus.transaction.transaction_provider".');
        }

        $services
            ->set(WrapInTransactionMiddleware::class)
                ->args([
                    service($config['transaction']['transaction_provider']),
                ])
                ->tag('trollbus.middleware', ['priority' => 700]);
    }

    private function configureEntityHandler(NodeBuilder $config): void
    {
        $config
            ->arrayNode('entity_handler')
            ->canBeEnabled()
            ->children()
                ->stringNode('entity_finder')
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
        if (false === $config['entity_handler']['enabled']) {
            return;
        }

        if (null === $config['entity_handler']['entity_finder']) {
            throw new LogicException('Entity finder not defined. Please, define config "trollbus.entity_handler.entity_finder".');
        }

        $services->alias('trollbus.entity_handler.default_entity_finder', $config['entity_handler']['entity_finder']);

        if (null === $config['entity_handler']['entity_saver']) {
            throw new LogicException('Entity saver not defined. Please, define config "trollbus.entity_handler.entity_saver".');
        }

        $services->alias('trollbus.entity_handler.default_entity_saver', $config['entity_handler']['entity_saver']);

        if (null === $config['entity_handler']['criteria_resolver']) {
            $services->set(PropertyCriteriaResolver::class);
            $config['entity_handler']['criteria_resolver'] = PropertyCriteriaResolver::class;
        }

        $services->alias('trollbus.entity_handler.default_criteria_resolver', $config['entity_handler']['criteria_resolver']);
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
                        ->defaultNull()
                        ->end()
                    ->booleanNode('entity_saver_flush')
                        ->defaultFalse()
                        ->end()
                    ->booleanNode('flusher')
                        ->defaultTrue();
    }

    /**
     * @psalm-param Config $config
     */
    private function loadDoctrineOrmBridge(array &$config, ServicesConfigurator $services): void
    {
        if (false === $config['doctrine_orm_bridge']['enabled']) {
            return;
        }

        if (false === InstalledVersions::isInstalled('trollbus/doctrine-orm-bridge', false)) {
            throw new LogicException('Package "trollbus/doctrine-orm-bridge" is not installed.');
        }

        if ($config['transaction']['enabled'] && null === $config['transaction']['transaction_provider']) {
            $services
                ->set(DoctrineTransactionProvider::class)
                    ->args([
                        service($config['doctrine_orm_bridge']['manager_registry']),
                        $config['doctrine_orm_bridge']['manager'],
                    ]);
            $config['transaction']['transaction_provider'] = DoctrineTransactionProvider::class;
        }

        if ($config['entity_handler']['enabled']) {
            if (null === $config['entity_handler']['entity_finder']) {
                $services->set(DoctrineEntityFinder::class)
                    ->args([
                        service($config['doctrine_orm_bridge']['manager_registry']),
                    ]);
                $config['entity_handler']['entity_finder'] = DoctrineEntityFinder::class;
            }

            if (null === $config['entity_handler']['entity_saver']) {
                $services->set(DoctrineEntitySaver::class)
                    ->args([
                        service($config['doctrine_orm_bridge']['manager_registry']),
                        $config['doctrine_orm_bridge']['entity_saver_flush'],
                    ]);
                $config['entity_handler']['entity_saver'] = DoctrineEntitySaver::class;
            }
        }

        if ($config['doctrine_orm_bridge']['flusher']) {
            $services
                ->set(FlusherMiddleware::class)
                ->args([
                    service($config['doctrine_orm_bridge']['manager_registry']),
                    $config['doctrine_orm_bridge']['manager'],
                ])
                ->tag('trollbus.middleware', ['priority' => 500]);
        }
    }
}

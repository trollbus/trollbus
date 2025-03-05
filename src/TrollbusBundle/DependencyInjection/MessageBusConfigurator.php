<?php

declare(strict_types=1);

namespace Trollbus\TrollbusBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Trollbus\Message\Message;
use Trollbus\MessageBus\EntityHandler\EntityHandler;
use Trollbus\MessageBus\Handler\CallableHandler;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

final class MessageBusConfigurator
{
    public const MESSAGE_BUS = 'trollbus';
    public const HANDLER_REGISTRY = 'trollbus.handler_registry';
    public const HANDLER_TAG = 'trollbus.handler';
    public const HANDLER_TAG_MESSAGE = 'message';
    public const MIDDLEWARE_TAG = 'trollbus.middleware';
    public const DEFAULT_MESSAGE_ID_GENERATOR = 'trollbus.message_id.default_generator';
    public const DEFAULT_TRANSACTION_PROVIDER = 'trollbus.transaction.default_transaction_provider';
    public const DEFAULT_ENTITY_FINDER = 'trollbus.entity_handler.default_entity_finder';
    public const DEFAULT_ENTITY_SAVER = 'trollbus.entity_handler.default_entity_saver';
    public const DEFAULT_CRITERIA_RESOLVER = 'trollbus.entity_handler.default_criteria_resolver';

    private static int $counter = 0;

    public function __construct(
        private readonly ContainerConfigurator $di,
    ) {}

    /**
     * @param class-string<Message> $message
     * @param non-empty-string $service
     */
    public function handler(string $message, string $service): self
    {
        $this->di
            ->services()
            ->get($service)
            ->tag(self::HANDLER_TAG, [self::HANDLER_TAG_MESSAGE => $message]);

        return $this;
    }

    /**
     * @param class-string<Message> $message
     * @param non-empty-string $service
     * @param non-empty-string|null $handlerId
     */
    public function callableHandler(string $message, string $service, string $method = '__invoke', ?string $handlerId = null): self
    {
        $serviceId = self::serviceId($message);
        $this->di
            ->services()
            ->set($serviceId, CallableHandler::class)
                ->args([
                    $handlerId ?? $serviceId,
                    [service($service), $method],
                ])
                ->tag(self::HANDLER_TAG, [self::HANDLER_TAG_MESSAGE => $message]);

        return $this;
    }

    /**
     * @param class-string<Message> $message
     * @param non-empty-array<non-empty-string, non-empty-string> $findBy
     */
    public function entityHandler(
        string $message,
        string $entityClass,
        string $handlerMethod,
        array $findBy,
        ?string $factoryMethod = null,
        string $entityFinder = 'trollbus.entity_handler.default_entity_finder',
        string $entitySaver = 'trollbus.entity_handler.default_entity_saver',
        string $criteriaResolver = 'trollbus.entity_handler.default_criteria_resolver',
        ?string $handlerId = null,
    ): self {
        $serviceId = self::serviceId($message);
        $this->di
            ->services()
            ->set($serviceId, EntityHandler::class)
                ->args([
                    $handlerId ?? $serviceId,
                    service($entityFinder),
                    service($criteriaResolver),
                    service($entitySaver),
                    $entityClass,
                    $handlerMethod,
                    $findBy,
                    $factoryMethod,
                ])
                ->tag(self::HANDLER_TAG, [self::HANDLER_TAG_MESSAGE => $message]);

        return $this;
    }

    /**
     * @param class-string<Message> $message
     *
     * @return non-empty-string
     */
    public static function serviceId(string $message): string
    {
        $serviceId = \sprintf('trollbus.handler.%s.%s', $message, self::$counter);
        ++self::$counter;

        return $serviceId;
    }
}

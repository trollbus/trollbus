<?php

declare(strict_types=1);

namespace Trollbus\TrollbusBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Trollbus\Message\Event;
use Trollbus\Message\Message;
use Trollbus\MessageBus\Handler;
use Trollbus\MessageBus\Handler\CallableHandler;
use Trollbus\MessageBus\Handler\EventHandler;
use Trollbus\MessageBus\Middleware\HandlerWithMiddlewares;

final class MessageBusPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function __construct(
        private readonly Config $config,
    ) {}

    public function process(ContainerBuilder $container): void
    {
        // $middlewares = $this->findAndSortTaggedServices($this->config->middlewareTag, $container);

        // todo 2. handler middlewares

        /** @var array<class-string<Message>, $handlerMap */
        $handlerMap = [];

        /**
         * @var class-string<Message> $messageClass
         * @var list<non-empty-string> $messageHandlerIds
         */
        foreach ($this->getHandlersMap($container) as $messageClass => $messageHandlerIds) {
            if (is_subclass_of($messageClass, Event::class)) {
                $handlerId = \sprintf('%s.handler.%s', $this->config->messageBus, $messageClass);
                $container->register($handlerId, EventHandler::class)
                    ->setArguments([
                        array_map(static fn(string $messageHandlerId) => new Reference($messageHandlerId), $messageHandlerIds),
                    ]);
                $handlerMap[$messageClass] = $handlerId;
            } elseif (1 === \count($messageHandlerIds)) {
                $handlerMap[$messageClass] = $messageHandlerIds[0];
            } else {
                throw new LogicException(\sprintf('Expects only one handler of message %s', $messageClass));
            }
        }

        // todo 3. collect handlers
        // todo 4. check handlers count
        // todo 5. handler registry
        // todo 6. message bus
    }

    /**
     * @return list<class-string<Message>, list<non-empty-string>>
     */
    private function getHandlersMap(ContainerBuilder $container): array
    {
        /** @var array<class-string<Message>, list<non-empty-string>> $handlersMap */
        $handlersMap = [];

        foreach ($container->getDefinitions() as $id => $definition) {
            if (!$definition->hasTag($this->config->handlerTag)) {
                continue;
            }

            $definitionClass = $definition->getClass();

            foreach ($definition->getTag($this->config->handlerTag) as $tag) {
                $messageClass = $tag[$this->config->handlerTagMessage]
                    ?? throw new LogicException(\sprintf(
                        'Service "%s" tagged by "%s" requires tag attribute "%s".',
                        $id,
                        $this->config->handlerTag,
                        $this->config->handlerTagMessage,
                    ));

                $messageClass = self::getFqcn($messageClass);

                if (!is_subclass_of($messageClass, Message::class)) {
                    throw new LogicException(\sprintf(
                        'Service "%s" tagged by "%s" contains invalid message class "%s" in attribute "%s".',
                        $id,
                        $this->config->handlerTag,
                        $messageClass,
                        $this->config->handlerTagMessage,
                    ));
                }

                if (is_subclass_of($definitionClass, Handler::class)) {
                    $handlerId = $id;
                } else {
                    $handlerMethod = $tag[$this->config->handlerTagMethod]
                        ?? throw new LogicException(\sprintf(
                            'Service "%s" tagged by "%s" requires tag attribute "%s".',
                            $id,
                            $this->config->handlerTag,
                            $this->config->handlerTagMethod,
                        ));
                    $handlerId = \sprintf('%s.handler.%s.%s', $this->config->messageBus, $messageClass, \count($handlersMap[$messageClass] ?? []));
                    $container->register($handlerId, CallableHandler::class)
                        ->setArguments([
                            $tag[$this->config->handlerTagId] ?? $handlerId,
                            [new Reference($id), $handlerMethod],
                        ]);
                }

                $handlerMiddlewares = (array) ($tag[$this->config->handlerTagMiddlewares] ?? []);

                if (\count($handlerMiddlewares) > 0) {
                    $container->register($handlerId . '.with_middlewares', HandlerWithMiddlewares::class)
                        ->setDecoratedService($handlerId)
                        ->setArguments([
                            new Reference($handlerId . '.with_middlewares.inner'),
                            array_map(static fn(string $handlerMiddlewareId) => new Reference($handlerMiddlewareId), $handlerMiddlewares),
                        ]);
                }

                $async = (bool) ($tag[$this->config->handlerTagAsync] ?? false);

                if ($async) {
                    throw new LogicException(\sprintf('Async handlers not supported yet. Please, fix service "%s".', $id));
                }

                $handlersMap[$messageClass][] = $handlerId;

                if (!\in_array($id, $handlersMap[$messageClass] ?? [], true)) {
                    $handlersMap[$messageClass][] = $id;
                }
            }
        }

        return $handlersMap;
    }

    /**
     * @return class-string
     */
    private static function getFqcn(string $class): string
    {
        try {
            return (new \ReflectionClass($class))->getName();
        } catch (\ReflectionException $e) {
            throw new LogicException($e->getMessage(), $e->getCode(), $e);
        }
    }
}

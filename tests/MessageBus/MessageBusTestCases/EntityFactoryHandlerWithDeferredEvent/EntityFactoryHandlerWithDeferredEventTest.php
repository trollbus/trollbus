<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\MessageBusTestCases\EntityFactoryHandlerWithDeferredEvent;

use PHPUnit\Framework\TestCase;
use Trollbus\MessageBus\DeferredEvent\DeferEventsMiddleware;
use Trollbus\MessageBus\DeferredEvent\DeferredEventsStorage;
use Trollbus\MessageBus\DeferredEvent\HandleDeferredEventsMiddleware;
use Trollbus\MessageBus\EntityHandler\EntityFactoryHandler;
use Trollbus\MessageBus\EntityHandler\EntitySaver;
use Trollbus\MessageBus\Handler\CallableHandler;
use Trollbus\MessageBus\Handler\EventHandler;
use Trollbus\MessageBus\HandlerRegistry\ClassStringMap;
use Trollbus\MessageBus\HandlerRegistry\ClassStringMapHandlerRegistry;
use Trollbus\MessageBus\MessageBus;
use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\HandlerWithMiddlewares;

final class EntityFactoryHandlerWithDeferredEventTest extends TestCase
{
    private bool $commandAfterEntityCreatedWasHandled = false;

    public function testCanSave(): void
    {
        $bus = $this->createMessageBus(true);
        $bus->dispatch(new CreateEntity());

        self::assertTrue($this->commandAfterEntityCreatedWasHandled);
    }

    /**
     * When entity factory handler dispatch event, but EntitySaver can not save entity, dispatched events MUST NOT be
     * handled.
     */
    public function testCanNotSave(): void
    {
        $bus = $this->createMessageBus(false);

        try {
            $bus->dispatch(new CreateEntity());
        } catch (\RuntimeException) {
        }

        self::assertFalse($this->commandAfterEntityCreatedWasHandled);
    }

    private function createMessageBus(bool $canSave): MessageBus
    {
        $saver = new class ($canSave) implements EntitySaver {
            public function __construct(
                private bool $canSave,
            ) {}

            public function save(object $entity): void
            {
                if (false === $this->canSave) {
                    throw new \RuntimeException('Entity save error.');
                }
            }
        };

        $deferredEventsStorage = new DeferredEventsStorage();
        $deferEventsMiddleware = new DeferEventsMiddleware($deferredEventsStorage);
        $handleDeferredEventsMiddleware = new HandleDeferredEventsMiddleware($deferredEventsStorage);

        /** @var CallableHandler<void, EntityCreated> $entityCreatedHandler */
        $entityCreatedHandler = new CallableHandler(
            EntityCreated::class . '.handler',
            static fn(EntityCreated $event, MessageContext $messageContext) => $messageContext->dispatch(new CommandAfterEntityCreated()),
        );

        return new MessageBus(
            new ClassStringMapHandlerRegistry(
                (new ClassStringMap())
                    ->with(
                        CreateEntity::class,
                        new HandlerWithMiddlewares(
                            new EntityFactoryHandler(CreateEntity::class, $saver, Entity::class, 'create'),
                            [$handleDeferredEventsMiddleware],
                        ),
                    )->with(
                        EntityCreated::class,
                        new EventHandler([
                            $entityCreatedHandler,
                        ]),
                    )->with(
                        CommandAfterEntityCreated::class,
                        new CallableHandler(
                            CommandAfterEntityCreated::class,
                            function (): void {
                                $this->commandAfterEntityCreatedWasHandled = true;
                            },
                        ),
                    ),
            ),
            [$deferEventsMiddleware],
        );
    }
}

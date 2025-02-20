<?php

declare(strict_types=1);

namespace Trollbus\Tests\DoctrineORMBridge\Flusher;

use PHPUnit\Framework\TestCase;
use Trollbus\DoctrineORMBridge\Flusher\FlusherMiddleware;
use Trollbus\MessageBus\Handler\CallableHandler;
use Trollbus\MessageBus\HandlerRegistry\ClassStringMap;
use Trollbus\MessageBus\HandlerRegistry\ClassStringMapHandlerRegistry;
use Trollbus\MessageBus\MessageBus;
use Trollbus\MessageBus\MessageContext;
use Trollbus\Tests\DoctrineORMBridge\ManagerRegistry;

final class FlusherMiddlewareTest extends TestCase
{
    private ManagerRegistry $doctrine;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = new ManagerRegistry(__DIR__);
        $this->doctrine->createSchema();
    }

    public function test(): void
    {
        $messageBus = new MessageBus(
            new ClassStringMapHandlerRegistry(
                (new ClassStringMap())
                    ->with(
                        CreateFoo::class,
                        new CallableHandler(
                            'create foo',
                            /**
                             * @param MessageContext<void, CreateFoo> $messageContext
                             */
                            function (CreateFoo $command, MessageContext $messageContext): void {
                                $foo = new Foo($command->id, $command->title);
                                $this->doctrine->getManager()->persist($foo);

                                $messageContext->dispatch(new CreateBar($command->id, 'BAR: ' . $command->title));
                            },
                        ),
                    )->with(
                        CreateBar::class,
                        new CallableHandler('create bar', function (CreateBar $command): void {
                            $bar = new Bar($command->id, $command->title);
                            $this->doctrine->getManager()->persist($bar);
                        }),
                    ),
            ),
            [
                new FlusherMiddleware($this->doctrine),
            ],
        );

        $messageBus->dispatch(new CreateFoo('1', 'Title'));

        $em = $this->doctrine->getManager();
        $em->clear();

        $foo = $em->find(Foo::class, '1');
        self::assertInstanceOf(Foo::class, $foo);
        self::assertSame('1', $foo->getId());
        self::assertSame('Title', $foo->getTitle());

        $bar = $em->find(Bar::class, '1');
        self::assertInstanceOf(Bar::class, $bar);
        self::assertSame('1', $bar->getId());
        self::assertSame('BAR: Title', $bar->getTitle());
    }
}

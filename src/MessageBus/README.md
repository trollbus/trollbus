# Trollbus MessageBus

`Trollbus` is another yet Flexible Message Bus implementation with middleware and handler result support.

Inspired by [Telephantast](https://github.com/telephantast/telephantast). Most of the ideas were taken from there.

## Installation

```bash
composer require trollbus/message-bus
```

## Usage

Common usage:

```php
use Trollbus\MessageBus\CreatedAt\CreatedAtMiddleware;
use Trollbus\MessageBus\Handler\CallableHandler;
use Trollbus\MessageBus\HandlerRegistry\ClassStringMap;
use Trollbus\MessageBus\HandlerRegistry\ClassStringMapHandlerRegistry;
use Trollbus\MessageBus\Logging\LogMiddleware;
use Trollbus\MessageBus\MessageBus;
use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\MessageId\CausationIdMiddleware;
use Trollbus\MessageBus\MessageId\CorrelationIdMiddleware;
use Trollbus\MessageBus\MessageId\MessageIdMiddleware;
use Trollbus\MessageBus\MessageId\RandomMessageIdGenerator;
use Trollbus\MessageBus\Transaction\WrapInTransactionMiddleware;

// Create Message handler
$registerUserHandler = new \Trollbus\MessageBus\Handler\CallableHandler(
    id: 'register-user',
    handler: function(): void {
        // ... Some code
    },
);

// Configure handler registry
$handlerRegistry = new ClassStringMapHandlerRegistry(
    (new ClassStringMap())
        ->with(RegisterUser::class, $registerUserHandler),
);

// Configure Message Bus
$messageBus = new MessageBus(
    handlerRegistry: $handlerRegistry,
    middlewares: [
        new CreatedAtMiddleware(),
        new MessageIdMiddleware(
            use RandomMessageIdGenerator(),
        ),
        new CorrelationIdMiddleware(),
        new CausationIdMiddleware(),
        new LogMiddleware($logger), // Use any psr logger
        new WrapInTransactionMiddleware($transactionProvider),
    ],
);

// Use Message Bus
$messageBus->dispatch(new RegisterUser(
    username: 'choibm',
    password: '123',
));
```

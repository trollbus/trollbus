# Trollbus Message

`Trollbus` Message Contracts.

## Installation

```bash
composer require trollbus/message
```

## Usage

Message class:

```php
use Trollbus\Message\Message;

/**
 * @implements Message<void>
 */
final readonly class RegisterUser implements Message
{
    public function __construct(
        public string $username,
        #[\SensitiveParameter]
        public string $password,
    ) {}
}
```

Event class:

```php
use Trollbus\Message\Event;

final readonly class UserWasRegister implements Event
{
    public function __construct(
        public string $username,
    ) {}
}
```

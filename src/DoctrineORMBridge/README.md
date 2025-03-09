# Trollbus DoctrineORMBridge

Integrate `Trollbus` with [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/current/index.html).

Provides:

- Transactional Provider (`DoctrineTransactionProvider`).
- Finder (`DoctrineEntityFinder`) and Saver (`DoctrineEntitySaver`) implementations for EntityHandler.
- Automatic flush entities (`FlusherMiddleware`).
- Automatic reset entity manager, if it was closed (`ResetEntityManagerMiddleware`).

## Installation

```bash
composer require trollbus/doctrine-orm-bridge
```

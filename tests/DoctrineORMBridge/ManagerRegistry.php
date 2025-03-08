<?php

declare(strict_types=1);

namespace Trollbus\Tests\DoctrineORMBridge;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\AbstractManagerRegistry;
use Doctrine\Persistence\Proxy;

/**
 * @internal
 * @psalm-internal Trollbus\Tests
 */
final class ManagerRegistry extends AbstractManagerRegistry
{
    private const CONNECTION_SERVICE = 'doctrine.connection.default';
    private const EM_SERVICE = 'doctrine.entity_manager.default';

    private ?EntityManager $em = null;

    private ?Connection $conn = null;

    public function __construct(
        private readonly string $entityDir,
    ) {
        parent::__construct(
            name: 'doctrine',
            connections: ['default' => self::CONNECTION_SERVICE],
            managers: ['default' => self::EM_SERVICE],
            defaultConnection: 'default',
            defaultManager: 'default',
            proxyInterfaceName: Proxy::class,
        );
    }

    public function createSchema(): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getManager();
        $allMetadata = $em->getMetadataFactory()->getAllMetadata();

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema($allMetadata);
        $schemaTool->createSchema($allMetadata);
    }

    #[\Override]
    protected function getService(string $name): object
    {
        switch ($name) {
            case self::CONNECTION_SERVICE:
                return $this->conn ??= DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
            case self::EM_SERVICE:
                if (null !== $this->em) {
                    return $this->em;
                }

                /** @var Connection $conn */
                $conn = $this->getService(self::CONNECTION_SERVICE);

                return $this->em = new EntityManager(
                    $conn,
                    ORMSetup::createAttributeMetadataConfiguration([$this->entityDir], true),
                );
        }

        throw new \RuntimeException("Invalid service {$name}");
    }

    #[\Override]
    protected function resetService(string $name): void
    {
        switch ($name) {
            case self::CONNECTION_SERVICE:
                $this->conn = null;
                break;
            case self::EM_SERVICE:
                $this->em = null;
                break;

            default:
                throw new \RuntimeException("Invalid service {$name}");
        }
    }
}

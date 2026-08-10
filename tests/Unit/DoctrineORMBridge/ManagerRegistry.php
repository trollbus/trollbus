<?php

declare(strict_types=1);

namespace Trollbus\Tests\Unit\DoctrineORMBridge;

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
        /** @psalm-suppress RedundantFunctionCallGivenDocblockType,RedundantFunctionCall In doctrine/persistence getAllMetadata() was return array instead list of metadata instances */
        $allMetadata = array_values($em->getMetadataFactory()->getAllMetadata());

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema($allMetadata);
        $schemaTool->createSchema($allMetadata);
    }

    /**
     * @psalm-suppress InvalidReturnType In doctrine/persistence return type was ObjectManager.
     */
    #[\Override]
    protected function getService(string $name): object
    {
        switch ($name) {
            case self::CONNECTION_SERVICE:
                /** @psalm-suppress InvalidReturnStatement In doctrine/persistence return type was ObjectManager. */
                return $this->conn ??= DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
            case self::EM_SERVICE:
                if (null !== $this->em) {
                    return $this->em;
                }

                /** @var Connection $conn */
                $conn = $this->getService(self::CONNECTION_SERVICE);
                $config = ORMSetup::createAttributeMetadataConfiguration([$this->entityDir], true);

                if (method_exists($config, 'enableNativeLazyObjects')) {
                    $config->enableNativeLazyObjects(version_compare(PHP_VERSION, '8.4.0', '>='));
                }

                return $this->em = new EntityManager(
                    $conn,
                    $config,
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

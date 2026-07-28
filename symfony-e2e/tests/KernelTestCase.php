<?php

declare(strict_types=1);

namespace App\Tests;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\NullOutput;
use Trollbus\MessageBus\MessageBus;

abstract class KernelTestCase extends \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
{
    final protected function bootKernelAndInitDb(): void
    {
        self::bootKernel();
        self::updateSchema();
    }

    final protected static function getMessageBus(): MessageBus
    {
        $bus = self::getContainer()->get(MessageBus::class);
        \assert($bus instanceof MessageBus);

        return $bus;
    }

    final protected static function getDoctrine(): ManagerRegistry
    {
        $doctrine = self::getContainer()->get('doctrine');
        \assert($doctrine instanceof ManagerRegistry);

        return $doctrine;
    }

    final protected function updateSchema(): void
    {
        self::executeCliCommand(['doctrine:schema:update', '--force']);
    }

    /**
     * @param non-empty-list<non-empty-string> $argv
     */
    final protected static function executeCliCommand(array $argv): void
    {
        $kernel = self::$kernel;

        if (null === $kernel) {
            throw new \LogicException('Kernel was not booted');
        }

        $app = new Application($kernel);
        $app->setAutoExit(false);
        $app->setCatchExceptions(false);
        $app->setCatchErrors(false);
        $app->run(new ArgvInput(['', ...$argv]), new NullOutput());
    }
}

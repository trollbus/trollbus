<?php

declare(strict_types=1);

namespace App\Tests;

use Trollbus\MessageBus\MessageBus;

abstract class KernelTestCase extends \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
{
    final public static function getMessageBus(): MessageBus
    {
        $bus = self::getContainer()->get(MessageBus::class);
        \assert($bus instanceof MessageBus);

        return $bus;
    }
}

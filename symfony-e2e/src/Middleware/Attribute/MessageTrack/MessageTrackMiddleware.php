<?php

declare(strict_types=1);

namespace App\Middleware\Attribute\MessageTrack;

use Trollbus\MessageBus\MessageContext;
use Trollbus\MessageBus\Middleware\Pipeline;
use Trollbus\TrollbusBundle\Attribute\Middleware;

final class MessageTrackMiddleware
{
    public const string MIDDLEWARE = 'app.trollbus.middleware.message_track';

    #[Middleware(serviceId: self::MIDDLEWARE)]
    public function trackMessage(Pipeline $pipeline, MessageContext $context): mixed
    {
        self::addStamp($context);
        $context->getStamp(MessageTrack::class)?->addTrack('handler');

        return $pipeline->continue();
    }

    #[Middleware(global: true)]
    public function trackMessageGlobal(Pipeline $pipeline, MessageContext $context): mixed
    {
        self::addStamp($context);
        $context->getStamp(MessageTrack::class)?->addTrack('global');

        return $pipeline->continue();
    }

    private static function addStamp(MessageContext $context): void
    {
        if (!$context->hasStamp(MessageTrack::class)) {
            $context->addStamps(new MessageTrack());
        }
    }
}

<?php

declare(strict_types=1);

namespace App\EntityHandler\Attribute\EventListener;

use App\EntityHandler\Attribute\Event\PostCreated;
use App\EntityHandler\Attribute\Event\PostUpdated;
use Trollbus\Message\Message;
use Trollbus\MessageBus\MessageContext;
use Trollbus\TrollbusBundle\Attribute\Handler;

final class PostListener
{
    /** @var \WeakMap<Message, list<string>> */
    private \WeakMap $listenersByCommand;

    public function __construct()
    {
        $this->listenersByCommand = new \WeakMap();
    }

    #[Handler]
    public function onPostCreated(PostCreated $event, MessageContext $context): void
    {
        $this->addListenerByChildContext(__FUNCTION__, $context);
    }

    #[Handler]
    public function onPostUpdated(PostUpdated $event, MessageContext $context): void
    {
        $this->addListenerByChildContext(__FUNCTION__, $context);
    }

    #[Handler]
    public function onPostCreatedOrUpdated(PostCreated|PostUpdated $event, MessageContext $context): void
    {
        $this->addListenerByChildContext(__FUNCTION__, $context);
    }

    /**
     * @return array<string>
     */
    public function getListenersByCommand(Message $command): array
    {
        return $this->listenersByCommand[$command] ?? [];
    }

    private function addListenerByChildContext(string $listener, MessageContext $context): void
    {
        $command = $context->parent->getMessage();
        \assert($command instanceof Message);

        if (isset($this->listenersByCommand[$command])) {
            $this->listenersByCommand[$command][] = $listener;
        } else {
            $this->listenersByCommand[$command] = [$listener];
        }
    }
}

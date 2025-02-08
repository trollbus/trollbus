<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\MessageContextStack;

use Trollbus\MessageBus\ReadonlyMessageContext;

final class MessageContextStack
{
    /** @var list<ReadonlyMessageContext> */
    private array $messageContexts = [];

    public function push(ReadonlyMessageContext $messageContext): void
    {
        $this->messageContexts[] = $messageContext;
    }

    /**
     * @return list<ReadonlyMessageContext>
     */
    public function pull(): array
    {
        $messageContexts =  $this->messageContexts;
        $this->messageContexts = [];

        return $messageContexts;
    }
}

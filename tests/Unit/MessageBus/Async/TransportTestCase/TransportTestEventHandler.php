<?php

declare(strict_types=1);

namespace Trollbus\Tests\Unit\MessageBus\Async\TransportTestCase;

use Trollbus\MessageBus\Handler;
use Trollbus\MessageBus\MessageContext;

/**
 * @implements Handler<void, TransportTestEvent>
 */
final class TransportTestEventHandler implements Handler
{
    /** @var list<non-empty-string> */
    private array $handledMessageIds = [];

    /**
     * @param non-empty-string $id
     */
    public function __construct(
        private readonly string $id,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function handle(MessageContext $messageContext): mixed
    {
        $this->handledMessageIds[] = $messageContext->getMessageId();

        return null;
    }

    /**
     * @return list<non-empty-string>
     */
    public function getHandledMessageIds(): array
    {
        return $this->handledMessageIds;
    }

    public function clearHandledMessageIds(): void
    {
        $this->handledMessageIds = [];
    }
}

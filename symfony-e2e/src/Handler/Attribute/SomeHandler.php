<?php

declare(strict_types=1);

namespace App\Handler\Attribute;

use Trollbus\MessageBus\MessageContext;
use Trollbus\TrollbusBundle\Attribute\Handler;

final class SomeHandler
{
    /** @var list<string> */
    private array $calledMessages = [];

    /**
     * @return list<string>
     */
    public function pullCalledMessages(): array
    {
        $calledMessages = $this->calledMessages;
        $this->calledMessages = [];

        return $calledMessages;
    }

    #[Handler]
    public function someCommandHandler(SomeCommand $command, MessageContext $context): true
    {
        $this->calledMessages[] = 'SomeCommand: ' . $command->arg;

        $context->dispatch(new SomeEvent());

        return true;
    }

    #[Handler(message: SomeEvent::class)]
    public function onSomeEvent1(): void
    {
        $this->calledMessages[] = 'SomeEvent: 1';
    }

    #[Handler(message: SomeEvent::class)]
    public function onSomeEvent2(): void
    {
        $this->calledMessages[] = 'SomeEvent: 2';
    }
}

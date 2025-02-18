<?php

declare(strict_types=1);

namespace Trollbus\Tests\MessageBus\MessageBusTestCases\EntityHandler;

use Trollbus\MessageBus\MessageContext;

final class Entity
{
    private function __construct(
        private string $id,
        private string $title,
        private ?string $description,
    ) {}

    public static function create(EditEntity $message): self
    {
        return new self(
            id: $message->id,
            title: '',
            description: '',
        );
    }

    /**
     * @param MessageContext<void, EditEntity> $messageContext
     */
    public function edit(EditEntity $message, MessageContext $messageContext): void
    {
        $this->title = $message->title;
        $this->description = $message->description;

        $messageContext->dispatch(new EntityEdited(
            id: $this->id,
            title: $this->title,
            description: $this->description,
        ));
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}

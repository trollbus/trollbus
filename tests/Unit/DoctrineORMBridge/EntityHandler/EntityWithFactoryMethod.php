<?php

declare(strict_types=1);

namespace Trollbus\Tests\Unit\DoctrineORMBridge\EntityHandler;

use Doctrine\ORM\Mapping as ORM;

/**
 * @final
 */
#[ORM\Entity]
class EntityWithFactoryMethod
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string')]
        private readonly string $id,
        #[ORM\Column(type: 'string')]
        private string $title = '',
    ) {}

    public static function createEntity(CreateEntityWithFactoryMethod $command): self
    {
        return new self(
            id: $command->id,
            title: $command->title,
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}

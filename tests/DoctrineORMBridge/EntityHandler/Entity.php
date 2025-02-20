<?php

declare(strict_types=1);

namespace Trollbus\Tests\DoctrineORMBridge\EntityHandler;

use Doctrine\ORM\Mapping as ORM;

/**
 * @final
 */
#[ORM\Entity]
class Entity
{
    #[ORM\Column(type: 'string')]
    private string $title = '';

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string')]
        private readonly string $id,
    ) {}

    public static function createFromCommand(EditEntity $command): self
    {
        return new self($command->id);
    }

    public function editEntity(EditEntity $command): void
    {
        $this->title = $command->title;
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

<?php

declare(strict_types=1);

namespace Trollbus\Tests\DoctrineORMBridge\Flusher;

use Doctrine\ORM\Mapping as ORM;

/**
 * @final
 */
#[ORM\Entity]
class Foo
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private readonly string $id,
        #[ORM\Column]
        private string $title,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}

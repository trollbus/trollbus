<?php

declare(strict_types=1);

namespace App\EntityHandler\MessageBusConfigurator;

use App\EntityHandler\MessageBusConfigurator\Command\CreateTag;
use App\EntityHandler\MessageBusConfigurator\Command\EditTag;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
final class Tag
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: UuidType::NAME)]
        public readonly Uuid $id,
        #[ORM\Column(type: Types::STRING)]
        public private(set) string $name,
    ) {}

    public static function create(CreateTag $command): self
    {
        return new self(
            id: $command->id,
            name: $command->name,
        );
    }

    public function edit(EditTag $command): void
    {
        $this->name = $command->name;
    }
}

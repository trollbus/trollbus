<?php

declare(strict_types=1);

namespace App\EntityHandler\Attribute;

use App\EntityHandler\Attribute\Command\CreatePost;
use App\EntityHandler\Attribute\Command\UpdatePost;
use App\EntityHandler\Attribute\Command\UpsertPost;
use App\EntityHandler\Attribute\Event\PostCreated;
use App\EntityHandler\Attribute\Event\PostUpdated;
use App\Middleware\Attribute\MessageTrack\MessageTrackMiddleware;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Trollbus\MessageBus\MessageContext;
use Trollbus\TrollbusBundle\Attribute\EntityFactoryHandler;
use Trollbus\TrollbusBundle\Attribute\EntityHandler;
use Trollbus\TrollbusBundle\Attribute\WithMiddleware;

#[ORM\Entity]
final class Post
{
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $updatedAt;

    private bool $upsertCreated = false;

    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: UuidType::NAME)]
        public readonly Uuid $id,
        #[ORM\Column(type: Types::STRING)]
        public private(set) string $title,
        #[ORM\Column(type: Types::TEXT)]
        public private(set) string $body,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
        public private(set) \DateTimeImmutable $createdAt,
    ) {
        $this->updatedAt = $this->createdAt;
    }

    #[EntityFactoryHandler]
    #[WithMiddleware(serviceId: MessageTrackMiddleware::MIDDLEWARE)]
    public static function createPost(CreatePost $command, MessageContext $context): self
    {
        $post = new self(
            id: $command->id,
            title: $command->title,
            body: $command->body,
            createdAt: $command->timestamp,
        );
        $context->dispatch(new PostCreated(
            id: $post->id,
            timestamp: $command->timestamp,
        ));

        return $post;
    }

    #[EntityHandler(findBy: ['id' => 'id'])]
    #[WithMiddleware(serviceId: MessageTrackMiddleware::MIDDLEWARE)]
    public function updatePost(UpdatePost $command, MessageContext $context): void
    {
        $this->title = $command->title;
        $this->body = $command->body;
        $this->updatedAt = $command->timestamp;

        $context->dispatch(new PostUpdated(
            id: $this->id,
            timestamp: $command->timestamp,
        ));
    }

    #[EntityHandler(findBy: ['id' => 'id'], factoryMethod: 'upsertCreatePost')]
    #[WithMiddleware(serviceId: MessageTrackMiddleware::MIDDLEWARE)]
    public function upsertPost(UpsertPost $command, MessageContext $context): void
    {
        // Skip, if post was created
        if ($this->upsertCreated) {
            return;
        }

        $this->title = $command->title;
        $this->body = $command->body;
        $this->updatedAt = $command->timestamp;

        $context->dispatch(new PostUpdated(
            id: $this->id,
            timestamp: $command->timestamp,
        ));
    }

    public static function upsertCreatePost(UpsertPost $command, MessageContext $context): self
    {
        $post = new self(
            id: $command->id,
            title: $command->title,
            body: $command->body,
            createdAt: $command->timestamp,
        );
        $post->upsertCreated = true;

        $context->dispatch(new PostCreated(
            id: $command->id,
            timestamp: $command->timestamp,
        ));

        return $post;
    }
}

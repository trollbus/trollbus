<?php

declare(strict_types=1);

namespace App\Tests\EntityHandler\Attribute;

use App\EntityHandler\Attribute\Command\CreatePost;
use App\EntityHandler\Attribute\Command\UpdatePost;
use App\EntityHandler\Attribute\Command\UpsertPost;
use App\EntityHandler\Attribute\EventListener\PostListener;
use App\EntityHandler\Attribute\Post;
use App\Middleware\Attribute\MessageTrack\MessageTrack;
use App\Tests\KernelTestCase;
use Symfony\Component\Uid\Uuid;
use Trollbus\Message\Message;
use Trollbus\MessageBus\EntityHandler\EntityNotFound;
use Trollbus\MessageBus\Envelope;

final class PostTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernelAndInitDb();
    }

    public function testCreate(): void
    {
        self::getMessageBus()->dispatch(
            $envelope = self::wrapMessage(
                new CreatePost(
                    id: $id = Uuid::v7(),
                    title: 'Test',
                    body: 'Test body',
                    timestamp: new \DateTimeImmutable('2026-01-01 00:00:00'),
                ),
            ),
        );

        // Assert, that post was created
        $post = self::findPost($id);

        // Assert post fields
        self::assertEquals($id, $post->id);
        self::assertSame('Test', $post->title);
        self::assertSame('Test body', $post->body);
        self::assertEquals(new \DateTimeImmutable('2026-01-01 00:00:00'), $post->createdAt);
        self::assertEquals(new \DateTimeImmutable('2026-01-01 00:00:00'), $post->updatedAt);

        // Assert events
        self::assertSame(['onPostCreated', 'onPostCreatedOrUpdated'], self::getCommandEvents($envelope->message));

        // Assert middlewares
        self::assertSame(['global', 'handler'], $envelope->getStamp(MessageTrack::class)?->tracks);
    }

    public function testUpdate(): void
    {
        self::getMessageBus()->dispatch(new CreatePost(
            id: $id = Uuid::v7(),
            title: 'Test',
            body: 'Test body',
            timestamp: new \DateTimeImmutable('2026-01-01 00:00:00'),
        ));
        self::getMessageBus()->dispatch(
            $envelope = self::wrapMessage(
                new UpdatePost(
                    id: $id,
                    title: 'Test updated',
                    body: 'Test body updated',
                    timestamp: new \DateTimeImmutable('2026-01-02 00:00:00'),
                ),
            ),
        );

        // Assert, that post was created
        $post = self::findPost($id);

        // Assert post fields
        self::assertEquals($id, $post->id);
        self::assertSame('Test updated', $post->title);
        self::assertSame('Test body updated', $post->body);
        self::assertEquals(new \DateTimeImmutable('2026-01-01 00:00:00'), $post->createdAt);
        self::assertEquals(new \DateTimeImmutable('2026-01-02 00:00:00'), $post->updatedAt);

        // Assert events
        self::assertSame(['onPostUpdated', 'onPostCreatedOrUpdated'], self::getCommandEvents($envelope->message));

        // Assert middlewares
        self::assertSame(['global', 'handler'], $envelope->getStamp(MessageTrack::class)?->tracks);
    }

    public function testUpdateThrowsEntityNotFound(): void
    {
        self::expectException(EntityNotFound::class);

        self::getMessageBus()->dispatch(
            new UpdatePost(
                id: Uuid::v7(),
                title: 'Test updated',
                body: 'Test body updated',
                timestamp: new \DateTimeImmutable('2026-01-02 00:00:00'),
            ),
        );
    }

    public function testUpsertNew(): void
    {
        self::getMessageBus()->dispatch(
            $envelope = self::wrapMessage(
                new UpsertPost(
                    id: $id = Uuid::v7(),
                    title: 'Test',
                    body: 'Test body',
                    timestamp: new \DateTimeImmutable('2026-01-01 00:00:00'),
                ),
            ),
        );

        // Assert, that post was created
        $post = self::findPost($id);

        // Assert post fields
        self::assertEquals($id, $post->id);
        self::assertSame('Test', $post->title);
        self::assertSame('Test body', $post->body);
        self::assertEquals(new \DateTimeImmutable('2026-01-01 00:00:00'), $post->createdAt);
        self::assertEquals(new \DateTimeImmutable('2026-01-01 00:00:00'), $post->updatedAt);

        // Assert events
        self::assertSame(['onPostCreated', 'onPostCreatedOrUpdated'], self::getCommandEvents($envelope->message));

        // Assert middlewares
        self::assertSame(['global', 'handler'], $envelope->getStamp(MessageTrack::class)?->tracks);
    }

    public function testUpsertExists(): void
    {
        self::getMessageBus()->dispatch(new CreatePost(
            id: $id = Uuid::v7(),
            title: 'Test',
            body: 'Test body',
            timestamp: new \DateTimeImmutable('2026-01-01 00:00:00'),
        ));
        self::getMessageBus()->dispatch(
            $envelope = self::wrapMessage(
                new UpsertPost(
                    id: $id,
                    title: 'Test updated',
                    body: 'Test body updated',
                    timestamp: new \DateTimeImmutable('2026-01-02 00:00:00'),
                ),
            ),
        );

        // Assert, that post was created
        $post = self::findPost($id);

        // Assert post fields
        self::assertEquals($id, $post->id);
        self::assertSame('Test updated', $post->title);
        self::assertSame('Test body updated', $post->body);
        self::assertEquals(new \DateTimeImmutable('2026-01-01 00:00:00'), $post->createdAt);
        self::assertEquals(new \DateTimeImmutable('2026-01-02 00:00:00'), $post->updatedAt);

        // Assert events
        self::assertSame(['onPostUpdated', 'onPostCreatedOrUpdated'], self::getCommandEvents($envelope->message));

        // Assert middlewares
        self::assertSame(['global', 'handler'], $envelope->getStamp(MessageTrack::class)?->tracks);
    }

    private static function wrapMessage(Message $message): Envelope
    {
        return Envelope::wrap($message, new MessageTrack());
    }

    private static function findPost(Uuid $id): Post
    {
        $post = self::getDoctrine()->getManager()->find(Post::class, $id);
        self::assertInstanceOf(Post::class, $post);

        return $post;
    }

    /**
     * @return list<string>
     */
    private static function getCommandEvents(Message $command): array
    {
        $listener = self::getContainer()->get(PostListener::class);
        \assert($listener instanceof PostListener);

        return $listener->getListenersByCommand($command);
    }
}

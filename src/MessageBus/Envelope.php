<?php

declare(strict_types=1);

namespace Trollbus\MessageBus;

use Trollbus\Message\Message;
use Trollbus\MessageBus\MessageId\MessageId;
use Trollbus\MessageBus\MessageId\MessageIdNotSet;

/**
 * @template-covariant TResult
 * @template-covariant TMessage of Message<TResult>
 */
final class Envelope
{
    /**
     * @param TMessage $message
     * @param array<class-string<Stamp>, Stamp> $stamps
     */
    private function __construct(
        public readonly Message $message,
        public readonly array $stamps = [],
    ) {}

    /**
     * @template TTResult
     * @template TTMessage of Message<TTResult>
     * @param TTMessage|Envelope<TTResult, TTMessage> $messageOrEnvelop
     * @return Envelope<TTResult, TTMessage>
     */
    public static function wrap(Message|self $messageOrEnvelop, Stamp ...$stamps): self
    {
        if ($messageOrEnvelop instanceof Message) {
            $envelop = new self($messageOrEnvelop);
        } else {
            $envelop = $messageOrEnvelop;
        }

        /** @var Envelope<TTResult, TTMessage> $envelop */
        return $envelop->withStamps(...$stamps);
    }

    /**
     * @return non-empty-string
     * @throws MessageIdNotSet
     */
    public function getMessageId(): string
    {
        return $this->getStamp(MessageId::class)?->messageId ?? throw new MessageIdNotSet('Message id not set.');
    }

    /**
     * @return class-string<TMessage>
     */
    public function getMessageClass(): string
    {
        return $this->message::class;
    }

    /**
     * @param TMessage $message
     * @return self<TResult, TMessage>
     * @psalm-suppress InvalidTemplateParam
     */
    public function withMessage(Message $message): self
    {
        return new self($message, $this->stamps);
    }

    /**
     * @param class-string<Stamp> $class
     */
    public function hasStamp(string $class): bool
    {
        return isset($this->stamps[$class]);
    }

    /**
     * @template TStamp of Stamp
     * @param class-string<TStamp> $class
     * @return TStamp|null
     */
    public function getStamp(string $class): ?Stamp
    {
        /** @var TStamp|null */
        return $this->stamps[$class] ?? null;
    }

    /**
     * @return self<TResult, TMessage>
     */
    public function withStamps(Stamp ...$stamps): self
    {
        $newStamps = $this->stamps;

        foreach ($stamps as $stamp) {
            $newStamps[$stamp::class] = $stamp;
        }

        return new self($this->message, $newStamps);
    }

    /**
     * @param class-string<Stamp> $classes
     * @return self<TResult, TMessage>
     */
    public function withoutStamps(string ...$classes): self
    {
        $newStamps = $this->stamps;

        foreach ($classes as $class) {
            unset($newStamps[$class]);
        }

        return new self($this->message, $newStamps);
    }
}

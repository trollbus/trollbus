<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus;

use Kenny1911\SisyphBus\Message\Message;

/**
 * @template-covariant TResult
 * @template-covariant TMessage of Message<TResult>
 */
final class Envelop
{
    /** @var TMessage */
    public readonly Message $message;

    /** @var array<class-string<Stamp>, Stamp> */
    public readonly array $stamps;

    /**
     * @param TMessage $message
     * @param array<class-string<Stamp>, Stamp> $stamps
     */
    private function __construct(Message $message, array $stamps = [])
    {
        $this->message = $message;
        $this->stamps = $stamps;
    }

    /**
     * @template TTResult
     * @template TTMessage of Message<TTResult>
     * @param TTMessage|Envelop<TTResult, TTMessage> $messageOrEnvelop
     * @return Envelop<TTResult, TTMessage>
     */
    public static function wrap(Message|self $messageOrEnvelop, Stamp ...$stamps): self
    {
        if ($messageOrEnvelop instanceof Message) {
            $envelop = new self($messageOrEnvelop);
        } else {
            $envelop = $messageOrEnvelop;
        }

        /** @var Envelop<TTResult, TTMessage> $envelop */
        return $envelop->withStamps(...$stamps);
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

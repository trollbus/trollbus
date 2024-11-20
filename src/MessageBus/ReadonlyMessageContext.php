<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus;

use Kenny1911\SisyphBus\Message\Message;
use Kenny1911\SisyphBus\MessageBus\MessageId\Exception\MessageIdNotSet;
use Kenny1911\SisyphBus\MessageBus\MessageId\MessageId;

/**
 * @template-covariant TResult
 * @template-covariant TMessage of Message<TResult>
 */
abstract class ReadonlyMessageContext
{
    /** @var Envelope<TResult, TMessage> */
    public Envelope $envelop;

    public readonly ?self $parent;

    /** @var array<class-string<ContextAttribute>, ContextAttribute> */
    protected array $attributes = [];

    /**
     * @param Envelope<TResult, TMessage> $envelop
     */
    protected function __construct(Envelope $envelop, ?self $parent)
    {
        $this->envelop = $envelop;
        $this->parent = $parent;
    }

    /**
     * @return TMessage
     */
    public function getMessage(): Message
    {
        return $this->envelop->message;
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
        return $this->getMessage()::class;
    }

    /**
     * @return array<class-string<Stamp>, Stamp>
     */
    public function getStamps(): array
    {
        return $this->envelop->stamps;
    }

    /**
     * @param class-string<Stamp> $class
     */
    public function hasStamp(string $class): bool
    {
        return $this->envelop->hasStamp($class);
    }

    /**
     * @template TStamp of Stamp
     * @param class-string<TStamp> $class
     * @return TStamp|null
     */
    public function getStamp(string $class): ?Stamp
    {
        return $this->envelop->getStamp($class);
    }

    /**
     * @return array<class-string<ContextAttribute>, ContextAttribute>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param class-string<ContextAttribute> $class
     */
    public function hasAttribute(string $class): bool
    {
        return isset($this->attributes[$class]);
    }

    /**
     * @template TContextAttribute of ContextAttribute
     * @param class-string<TContextAttribute> $class
     * @return TContextAttribute|null
     */
    public function getAttribute(string $class): ?ContextAttribute
    {
        /** @var TContextAttribute|null */
        return $this->attributes[$class] ?? null;
    }
}

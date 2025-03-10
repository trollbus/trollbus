<?php

declare(strict_types=1);

namespace Trollbus\MessageBus;

use Trollbus\Message\Message;
use Trollbus\MessageBus\MessageId\MessageIdNotSet;

/**
 * @template-covariant TResult
 * @template-covariant TMessage of Message<TResult>
 */
abstract class ReadonlyMessageContext
{
    /** @var array<class-string<ContextAttribute>, ContextAttribute> */
    protected array $attributes = [];

    /**
     * @param Envelope<TResult, TMessage> $envelop
     */
    protected function __construct(
        protected Envelope $envelop,
        public readonly ?self $parent,
    ) {}

    /**
     * @return Envelope<TResult, TMessage>
     */
    final public function getEnvelop(): Envelope
    {
        return $this->envelop;
    }

    /**
     * @return TMessage
     */
    final public function getMessage(): Message
    {
        return $this->envelop->message;
    }

    /**
     * @return non-empty-string
     * @throws MessageIdNotSet
     */
    final public function getMessageId(): string
    {
        return $this->envelop->getMessageId();
    }

    /**
     * @return class-string<TMessage>
     */
    final public function getMessageClass(): string
    {
        return $this->envelop->getMessageClass();
    }

    /**
     * @return array<class-string<Stamp>, Stamp>
     */
    final public function getStamps(): array
    {
        return $this->envelop->stamps;
    }

    /**
     * @param class-string<Stamp> $class
     */
    final public function hasStamp(string $class): bool
    {
        return $this->envelop->hasStamp($class);
    }

    /**
     * @template TStamp of Stamp
     * @param class-string<TStamp> $class
     * @return TStamp|null
     */
    final public function getStamp(string $class): ?Stamp
    {
        return $this->envelop->getStamp($class);
    }

    /**
     * @return array<class-string<ContextAttribute>, ContextAttribute>
     */
    final public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param class-string<ContextAttribute> $class
     */
    final public function hasAttribute(string $class): bool
    {
        return isset($this->attributes[$class]);
    }

    /**
     * @template TContextAttribute of ContextAttribute
     * @param class-string<TContextAttribute> $class
     * @return TContextAttribute|null
     */
    final public function getAttribute(string $class): ?ContextAttribute
    {
        /** @var TContextAttribute|null */
        return $this->attributes[$class] ?? null;
    }
}

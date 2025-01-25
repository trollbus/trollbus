<?php

declare(strict_types=1);

namespace Trollbus\MessageBus;

use Trollbus\Message\Message;

/**
 * @template TResult
 * @template TMessage of Message<TResult>
 * @extends ReadonlyMessageContext<TResult, TMessage>
 */
final class MessageContext extends ReadonlyMessageContext
{
    private MessageBus $messageBus;

    /**
     * @param Envelope<TResult, TMessage> $envelop
     */
    protected function __construct(MessageBus $messageBus, Envelope $envelop, ?self $parent)
    {
        parent::__construct($envelop, $parent);

        $this->messageBus = $messageBus;
    }

    /**
     * @template TTResult
     * @template TTMessage of Message<TTResult>
     * @param TTMessage|Envelope<TTResult, TTMessage> $messageOrEnvelop
     * @return self<TTResult, TTMessage>
     */
    public static function start(MessageBus $messageBus, Message|Envelope $messageOrEnvelop): self
    {
        return new self($messageBus, Envelope::wrap($messageOrEnvelop), null);
    }

    /**
     * @template TTResult
     * @template TTMessage of Message<TTResult>
     * @param TTMessage|Envelope<TTResult, TTMessage> $messageOrEnvelop
     * @return (TTResult is void ? null : TTResult)
     */
    public function dispatch(Message|Envelope $messageOrEnvelop): mixed
    {
        $child = new self($this->messageBus, Envelope::wrap($messageOrEnvelop), $this);

        foreach ($this->attributes as $attribute) {
            if ($attribute instanceof InheritanceContextAttribute) {
                $child->addAttributes($attribute);
            }
        }

        return $this->messageBus->handleContext($child);
    }

    public function addStamps(Stamp ...$stamps): void
    {
        $this->envelop = $this->envelop->withStamps(...$stamps);
    }

    /**
     * @param class-string<Stamp> ...$stamps
     */
    public function removeStamps(string ...$stamps): void
    {
        $this->envelop = $this->envelop->withoutStamps(...$stamps);
    }

    /**
     * @param TMessage $message
     */
    public function setMessage(Message $message): void
    {
        $this->envelop = $this->envelop->withMessage($message);
    }

    public function addAttributes(ContextAttribute ...$attributes): void
    {
        foreach ($attributes as $attribute) {
            $this->attributes[$attribute::class] = $attribute;
        }
    }

    public function removeAttributes(ContextAttribute ...$attributes): void
    {
        foreach ($attributes as $attribute) {
            unset($this->attributes[$attribute::class]);
        }
    }
}

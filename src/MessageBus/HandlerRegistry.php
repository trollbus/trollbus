<?php

declare(strict_types=1);

namespace Trollbus\MessageBus;

use Trollbus\Message\Message;
use Trollbus\MessageBus\HandlerRegistry\HandlerNotFound;

interface HandlerRegistry
{
    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     *
     * @param class-string<TMessage> $messageClass
     *
     * @return Handler<TResult, TMessage>
     *
     * @throws HandlerNotFound
     */
    public function get(string $messageClass): Handler;
}

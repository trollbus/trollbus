<?php

declare(strict_types=1);

namespace Kenny1911\SisyphBus\MessageBus;

use Kenny1911\SisyphBus\Message\Message;
use Kenny1911\SisyphBus\MessageBus\HandlerRegistry\HandlerNotFound;

interface HandlerRegistry
{
    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param class-string<TMessage> $messageClass
     * @return Handler<TResult, TMessage>
     * @throws HandlerNotFound
     */
    public function get(string $messageClass): Handler;
}

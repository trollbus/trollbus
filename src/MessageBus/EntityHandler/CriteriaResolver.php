<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\EntityHandler;

use Trollbus\Message\Message;

interface CriteriaResolver
{
    /**
     * @param non-empty-array<non-empty-string, non-empty-string> $findBy
     *   Map, where key is message property and value is entity property
     *
     * @return non-empty-array<non-empty-string, mixed>
     */
    public function resolve(Message $message, array $findBy): array;
}

<?php

declare(strict_types=1);

namespace Trollbus\MessageBus\EntityHandler;

use Trollbus\Message\Message;

final class PropertyCriteriaResolver implements CriteriaResolver
{
    #[\Override]
    public function resolve(Message $message, array $findBy): array
    {
        $criteria = [];

        foreach ($findBy as $messageProperty => $entityProperty) {
            /** @psalm-suppress MixedAssignment */
            $criteria[$entityProperty] = $message->{$messageProperty};
        }

        return $criteria;
    }
}

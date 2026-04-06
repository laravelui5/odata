<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning;

final readonly class ExpandList
{
    /** @param list<ExpandItem> $items */
    public function __construct(public readonly array $items = []) {}

    public function isEmpty(): bool
    {
        return $this->items === [];
    }
}

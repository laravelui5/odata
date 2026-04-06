<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning;

final readonly class SelectList
{
    /** @param list<SelectItem> $items  Empty list means select all (*). */
    public function __construct(public readonly array $items = []) {}

    public function isSelectAll(): bool
    {
        return $this->items === [];
    }
}

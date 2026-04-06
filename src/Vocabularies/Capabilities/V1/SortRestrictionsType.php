<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class SortRestrictionsType
{
    public function __construct(
        public readonly array $ascendingOnlyProperties,
        public readonly array $descendingOnlyProperties,
        public readonly array $nonSortableProperties,
    ) {}
}

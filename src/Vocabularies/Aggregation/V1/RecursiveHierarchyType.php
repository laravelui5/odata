<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Aggregation\V1;

final readonly class RecursiveHierarchyType
{
    public function __construct(
        public readonly string $nodeProperty,
        public readonly string $parentNavigationProperty,
    ) {}
}

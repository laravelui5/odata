<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Analytics\V1;

final readonly class AggregatedPropertyType
{
    public function __construct(
        public readonly string $name,
        public readonly string $aggregationMethod,
        public readonly string $aggregatableProperty,
    ) {}
}

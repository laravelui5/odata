<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Aggregation\V1;

final readonly class AggregatablePropertyType
{
    public function __construct(
        public readonly string $property,
        public readonly array $supportedAggregationMethods,
        public readonly ?string $recommendedAggregationMethod = null,
    ) {}
}

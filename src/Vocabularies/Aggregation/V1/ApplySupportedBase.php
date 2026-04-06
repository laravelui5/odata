<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Aggregation\V1;

final readonly class ApplySupportedBase
{
    public function __construct(
        public readonly array $transformations,
        public readonly array $customAggregationMethods,
        public readonly mixed $rollup,
        public readonly bool $from,
    ) {}
}

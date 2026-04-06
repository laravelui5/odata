<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Aggregation\V1;

final readonly class CustomAggregateType
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
    ) {}
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Aggregation\V1;

final readonly class ApplySupportedType
{
    public function __construct(
        public readonly bool $propertyRestrictions,
        public readonly array $groupableProperties,
        public readonly array $aggregatableProperties,
    ) {}
}

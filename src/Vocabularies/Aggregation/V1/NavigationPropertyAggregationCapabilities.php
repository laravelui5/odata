<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Aggregation\V1;

/**
 * Aggregation capabilities on a navigation path
 */
final readonly class NavigationPropertyAggregationCapabilities
{
    public function __construct(
        public readonly array $customAggregates,
        public readonly mixed $applySupported = null,
    ) {}
}

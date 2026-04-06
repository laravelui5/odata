<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Reference to a recommendation list
 */
final readonly class RecommendationListType
{
    public function __construct(
        public readonly string $collectionPath,
        public readonly string $rankProperty,
        public readonly array $binding,
    ) {}
}

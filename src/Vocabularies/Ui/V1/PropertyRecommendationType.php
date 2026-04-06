<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Base type containing recommendations for an entity type property
 */
final readonly class PropertyRecommendationType
{
    public function __construct(
        public readonly mixed $recommendedFieldValue,
        public readonly bool $recommendedFieldIsSuggestion,
        public readonly ?string $recommendedFieldDescription = null,
        public readonly ?float $recommendedFieldScoreValue = null,
    ) {}
}

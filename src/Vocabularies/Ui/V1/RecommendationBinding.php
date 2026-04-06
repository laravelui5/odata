<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

final readonly class RecommendationBinding
{
    public function __construct(
        public readonly string $localDataProperty,
        public readonly string $valueListProperty,
    ) {}
}

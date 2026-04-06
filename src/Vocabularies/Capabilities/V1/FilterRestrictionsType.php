<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class FilterRestrictionsType
{
    public function __construct(
        public readonly array $requiredProperties,
        public readonly array $nonFilterableProperties,
        public readonly array $filterExpressionRestrictions,
    ) {}
}

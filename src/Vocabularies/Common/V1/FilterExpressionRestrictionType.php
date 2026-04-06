<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

final readonly class FilterExpressionRestrictionType
{
    public function __construct(
        public readonly ?string $property = null,
        public readonly ?string $allowedExpressions = null,
    ) {}
}

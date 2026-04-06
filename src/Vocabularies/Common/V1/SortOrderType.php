<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

/**
 * Exactly one of `Property`, `DynamicProperty` and `Expression` must be present
 */
final readonly class SortOrderType
{
    public function __construct(
        public readonly ?string $property = null,
        public readonly ?string $dynamicProperty = null,
        public readonly mixed $expression = null,
        public readonly ?bool $descending = null,
    ) {}
}

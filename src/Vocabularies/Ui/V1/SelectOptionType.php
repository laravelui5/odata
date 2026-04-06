<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * List of value ranges for a single property
 */
final readonly class SelectOptionType
{
    public function __construct(
        public readonly array $ranges,
        public readonly ?string $propertyName = null,
        public readonly ?string $dynamicPropertyName = null,
    ) {}
}

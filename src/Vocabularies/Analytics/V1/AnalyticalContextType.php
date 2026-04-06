<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Analytics\V1;

/**
 * Exactly one of `Property` and `DynamicProperty` must be present
 */
final readonly class AnalyticalContextType
{
    public function __construct(
        public readonly bool $dimension,
        public readonly bool $measure,
        public readonly bool $accumulativeMeasure,
        public readonly ?string $property = null,
        public readonly ?string $dynamicProperty = null,
    ) {}
}

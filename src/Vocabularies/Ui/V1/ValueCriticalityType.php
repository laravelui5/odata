<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Assigns a fixed criticality to a primitive value. This information can be used for semantic coloring.
 */
final readonly class ValueCriticalityType
{
    public function __construct(
        public readonly mixed $value = null,
        public readonly mixed $criticality = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Assigns a label to a criticality. This information can be used for semantic coloring.
 */
final readonly class CriticalityLabelType
{
    public function __construct(
        public readonly mixed $criticality,
        public readonly string $label,
    ) {}
}

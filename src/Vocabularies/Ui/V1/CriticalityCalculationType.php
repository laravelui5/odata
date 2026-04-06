<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Describes how to calculate the criticality of a value depending on the improvement direction
 */
final readonly class CriticalityCalculationType
{
    public function __construct(
        public readonly bool $isRelativeDifference,
        public readonly mixed $improvementDirection,
        public readonly array $constantThresholds,
        public readonly mixed $referenceValue = null,
    ) {}
}

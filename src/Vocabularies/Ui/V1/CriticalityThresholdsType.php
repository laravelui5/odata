<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Thresholds for calculating the criticality of a value
 */
final readonly class CriticalityThresholdsType
{
    public function __construct(
        public readonly mixed $acceptanceRangeLowValue = null,
        public readonly mixed $acceptanceRangeHighValue = null,
        public readonly mixed $toleranceRangeLowValue = null,
        public readonly mixed $toleranceRangeHighValue = null,
        public readonly mixed $deviationRangeLowValue = null,
        public readonly mixed $deviationRangeHighValue = null,
    ) {}
}

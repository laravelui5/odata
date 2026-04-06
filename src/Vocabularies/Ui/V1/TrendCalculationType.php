<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Describes how to calculate the trend of a value
 */
final readonly class TrendCalculationType
{
    public function __construct(
        public readonly mixed $referenceValue,
        public readonly bool $isRelativeDifference,
        public readonly float $upDifference,
        public readonly float $strongUpDifference,
        public readonly float $downDifference,
        public readonly float $strongDownDifference,
    ) {}
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

final readonly class DataPointType
{
    public function __construct(
        public readonly mixed $value,
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly ?string $longDescription = null,
        public readonly mixed $targetValue = null,
        public readonly mixed $forecastValue = null,
        public readonly ?float $minimumValue = null,
        public readonly ?float $maximumValue = null,
        public readonly mixed $valueFormat = null,
        public readonly mixed $visualization = null,
        public readonly mixed $sampleSize = null,
        public readonly mixed $referencePeriod = null,
        public readonly mixed $criticality = null,
        public readonly ?string $criticalityLabels = null,
        public readonly mixed $criticalityRepresentation = null,
        public readonly mixed $criticalityCalculation = null,
        public readonly mixed $trend = null,
        public readonly mixed $trendCalculation = null,
        public readonly mixed $responsible = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

final readonly class FixedScaleMultipleStackedMeasuresBoundaryValuesType
{
    public function __construct(
        public readonly float $minimumValue,
        public readonly float $maximumValue,
    ) {}
}

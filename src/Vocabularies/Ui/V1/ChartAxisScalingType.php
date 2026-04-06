<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

final readonly class ChartAxisScalingType
{
    public function __construct(
        public readonly mixed $scaleBehavior,
        public readonly mixed $autoScaleBehavior = null,
        public readonly mixed $fixedScaleMultipleStackedMeasuresBoundaryValues = null,
    ) {}
}

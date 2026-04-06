<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Exactly one of `Measure` and `DynamicMeasure` must be present
 */
final readonly class ChartMeasureAttributeType
{
    public function __construct(
        public readonly bool $useSequentialColorLevels,
        public readonly ?string $measure = null,
        public readonly ?string $dynamicMeasure = null,
        public readonly mixed $role = null,
        public readonly ?string $dataPoint = null,
    ) {}
}

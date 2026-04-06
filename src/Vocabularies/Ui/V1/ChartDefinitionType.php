<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

final readonly class ChartDefinitionType
{
    public function __construct(
        public readonly mixed $chartType,
        public readonly array $measures,
        public readonly array $dynamicMeasures,
        public readonly array $measureAttributes,
        public readonly array $dimensions,
        public readonly array $dimensionAttributes,
        public readonly array $actions,
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly mixed $axisScaling = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

final readonly class ChartDimensionAttributeType
{
    public function __construct(
        public readonly array $valuesForSequentialColorLevels,
        public readonly array $emphasizedValues,
        public readonly ?string $dimension = null,
        public readonly mixed $role = null,
        public readonly ?int $hierarchyLevel = null,
        public readonly mixed $emphasisLabels = null,
    ) {}
}

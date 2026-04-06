<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Thresholds for an aggregation level
 */
final readonly class LevelThresholdsType
{
    public function __construct(
        public readonly array $aggregationLevel,
    ) {}
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

final readonly class ChartAxisAutoScaleBehaviorType
{
    public function __construct(
        public readonly bool $zeroAlwaysVisible,
        public readonly mixed $dataScope,
    ) {}
}

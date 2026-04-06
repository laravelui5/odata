<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Value range. If the range option only requires a single value, the value must be in the property Low
 */
final readonly class SelectionRangeType
{
    public function __construct(
        public readonly mixed $sign,
        public readonly mixed $option,
        public readonly mixed $low,
        public readonly mixed $high = null,
    ) {}
}

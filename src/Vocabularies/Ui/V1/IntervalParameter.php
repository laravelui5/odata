<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Interval parameter formed with a 'from' and a 'to' property
 */
final readonly class IntervalParameter
{
    public function __construct(
        public readonly string $propertyNameFrom,
        public readonly mixed $propertyValueFrom,
        public readonly string $propertyNameTo,
        public readonly mixed $propertyValueTo,
    ) {}
}

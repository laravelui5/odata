<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

final readonly class IntervalType
{
    public function __construct(
        public readonly string $lowerBoundary,
        public readonly bool $lowerBoundaryIncluded,
        public readonly string $upperBoundary,
        public readonly bool $upperBoundaryIncluded,
        public readonly ?string $label = null,
    ) {}
}

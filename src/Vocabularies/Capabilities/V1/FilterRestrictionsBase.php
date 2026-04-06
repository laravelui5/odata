<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class FilterRestrictionsBase
{
    public function __construct(
        public readonly bool $filterable,
        public readonly bool $requiresFilter,
        public readonly int $maxLevels,
    ) {}
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class SelectSupportType
{
    public function __construct(
        public readonly bool $supported,
        public readonly bool $instanceAnnotationsSupported,
        public readonly bool $expandable,
        public readonly bool $filterable,
        public readonly bool $searchable,
        public readonly bool $topSupported,
        public readonly bool $skipSupported,
        public readonly bool $computeSupported,
        public readonly bool $countable,
        public readonly bool $sortable,
    ) {}
}

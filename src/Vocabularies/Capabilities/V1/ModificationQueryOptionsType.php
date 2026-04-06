<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class ModificationQueryOptionsType
{
    public function __construct(
        public readonly bool $expandSupported,
        public readonly bool $selectSupported,
        public readonly bool $computeSupported,
        public readonly bool $filterSupported,
        public readonly bool $searchSupported,
        public readonly bool $sortSupported,
    ) {}
}

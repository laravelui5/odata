<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class ExpandRestrictionsBase
{
    public function __construct(
        public readonly bool $expandable,
        public readonly bool $streamsExpandable,
        public readonly int $maxLevels,
    ) {}
}

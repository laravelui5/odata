<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class SortRestrictionsBase
{
    public function __construct(
        public readonly bool $sortable,
    ) {}
}

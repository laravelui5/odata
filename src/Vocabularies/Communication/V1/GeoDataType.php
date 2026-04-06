<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

final readonly class GeoDataType
{
    public function __construct(
        public readonly ?string $uri = null,
        public readonly mixed $type = null,
    ) {}
}

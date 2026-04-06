<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

final readonly class PropertyRef
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $alias = null,
    ) {}
}

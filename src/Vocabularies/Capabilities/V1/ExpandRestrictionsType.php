<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class ExpandRestrictionsType
{
    public function __construct(
        public readonly array $nonExpandableProperties,
        public readonly array $nonExpandableStreamProperties,
    ) {}
}

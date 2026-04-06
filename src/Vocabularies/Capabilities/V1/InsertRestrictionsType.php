<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class InsertRestrictionsType
{
    public function __construct(
        public readonly array $nonInsertableProperties,
        public readonly array $nonInsertableNavigationProperties,
        public readonly array $requiredProperties,
        public readonly ?array $permissions = null,
    ) {}
}

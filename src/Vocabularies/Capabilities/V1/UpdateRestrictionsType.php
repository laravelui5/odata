<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class UpdateRestrictionsType
{
    public function __construct(
        public readonly array $nonUpdatableProperties,
        public readonly array $nonUpdatableNavigationProperties,
        public readonly array $requiredProperties,
    ) {}
}

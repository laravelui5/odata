<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class CountRestrictionsType
{
    public function __construct(
        public readonly array $nonCountableProperties,
        public readonly array $nonCountableNavigationProperties,
    ) {}
}

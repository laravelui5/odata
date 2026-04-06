<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class ScopeType
{
    public function __construct(
        public readonly string $scope,
        public readonly ?string $restrictedProperties = null,
    ) {}
}

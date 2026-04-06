<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Authorization\V1;

final readonly class AuthorizationScope
{
    public function __construct(
        public readonly string $scope,
        public readonly string $description,
        public readonly ?string $grant = null,
    ) {}
}

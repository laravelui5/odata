<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Authorization\V1;

final readonly class SecurityScheme
{
    public function __construct(
        public readonly string $authorization,
        public readonly array $requiredScopes,
    ) {}
}

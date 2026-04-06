<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class PermissionType
{
    public function __construct(
        public readonly string $schemeName,
        public readonly array $scopes,
    ) {}
}

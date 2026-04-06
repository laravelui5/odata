<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Authorization\V1;

final readonly class OAuthAuthorization
{
    public function __construct(
        public readonly array $scopes,
        public readonly ?string $refreshUrl = null,
    ) {}
}

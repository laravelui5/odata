<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Authorization\V1;

final readonly class OAuth2ClientCredentials
{
    public function __construct(
        public readonly string $tokenUrl,
    ) {}
}

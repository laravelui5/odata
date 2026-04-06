<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Authorization\V1;

final readonly class OAuth2AuthCode
{
    public function __construct(
        public readonly string $authorizationUrl,
        public readonly string $tokenUrl,
    ) {}
}

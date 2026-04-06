<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Authorization\V1;

/**
 * Security note: OAuth2 implicit grant is considered to be not secure and should not be used by clients, see [OAuth 2.0 Security Best Current Practice](https://datatracker.ietf.org/doc/html/draft-ietf-oauth-security-topics.html#name-implicit-grant).
 */
final readonly class OAuth2Implicit
{
    public function __construct(
        public readonly string $authorizationUrl,
    ) {}
}

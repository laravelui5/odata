<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Authorization\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Security note: OAuth2 implicit grant is considered to be not secure and should not be used by clients, see [OAuth 2.0 Security Best Current Practice](https://datatracker.ietf.org/doc/html/draft-ietf-oauth-security-topics.html#name-implicit-grant).
 */
final readonly class OAuth2Implicit extends OAuthAuthorization
{
    public const string TYPE = 'Org.OData.Authorization.V1.OAuth2Implicit';

    public const array FIELDS = [
        'Name' => ['name', 'Edm.String', true],
        'Description' => ['description', 'Edm.String', false],
        'Scopes' => ['scopes', 'Collection(Org.OData.Authorization.V1.AuthorizationScope)', false],
        'RefreshUrl' => ['refreshUrl', 'Edm.String', false],
        'AuthorizationUrl' => ['authorizationUrl', 'Edm.String', true],
    ];

    /**
     * @param list<AuthorizationScope>|Path|null $scopes
     */
    public function __construct(
        string|Path|null $name = null,
        string|Path|null $description = null,
        array|Path|null $scopes = null,
        string|Path|null $refreshUrl = null,
        public string|Path|null $authorizationUrl = null,
    ) {
        parent::__construct(
            name: $name,
            description: $description,
            scopes: $scopes,
            refreshUrl: $refreshUrl,
        );
    }
}

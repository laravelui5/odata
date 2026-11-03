<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Authorization\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class OAuth2ClientCredentials extends OAuthAuthorization
{
    public const string TYPE = 'Org.OData.Authorization.V1.OAuth2ClientCredentials';

    public const array FIELDS = [
        'Name' => ['name', 'Edm.String', true],
        'Description' => ['description', 'Edm.String', false],
        'Scopes' => ['scopes', 'Collection(Org.OData.Authorization.V1.AuthorizationScope)', false],
        'RefreshUrl' => ['refreshUrl', 'Edm.String', false],
        'TokenUrl' => ['tokenUrl', 'Edm.String', true],
    ];

    /**
     * @param list<AuthorizationScope>|Path|null $scopes
     */
    public function __construct(
        string|Path|null $name = null,
        string|Path|null $description = null,
        array|Path|null $scopes = null,
        string|Path|null $refreshUrl = null,
        public string|Path|null $tokenUrl = null,
    ) {
        parent::__construct(
            name: $name,
            description: $description,
            scopes: $scopes,
            refreshUrl: $refreshUrl,
        );
    }
}

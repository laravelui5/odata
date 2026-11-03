<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Authorization\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class OAuth2Password extends OAuthAuthorization
{
    public const string TYPE = 'Org.OData.Authorization.V1.OAuth2Password';

    public const array FIELDS = [
        'Name' => ['name', 'Edm.String', true],
        'Description' => ['description', 'Edm.String', false],
        'Scopes' => ['scopes', 'Collection(Org.OData.Authorization.V1.AuthorizationScope)', false],
        'RefreshUrl' => ['refreshUrl', 'Edm.String', false],
        'TokenUrl' => ['tokenUrl', 'Edm.String', true],
    ];

    /**
     * @param string|Path|null $name Name that can be used to reference the authorization scheme
     * @param string|Path|null $description Description of the authorization scheme
     * @param list<AuthorizationScope>|Path|null $scopes Available scopes
     * @param string|Path|null $refreshUrl Refresh Url
     * @param string|Path|null $tokenUrl Token Url
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

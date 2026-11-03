<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Authorization\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

abstract readonly class OAuthAuthorization extends Authorization
{
    public const string TYPE = 'Org.OData.Authorization.V1.OAuthAuthorization';

    public const array FIELDS = [
        'Name' => ['name', 'Edm.String', true],
        'Description' => ['description', 'Edm.String', false],
        'Scopes' => ['scopes', 'Collection(Org.OData.Authorization.V1.AuthorizationScope)', false],
        'RefreshUrl' => ['refreshUrl', 'Edm.String', false],
    ];

    /**
     * @param list<AuthorizationScope>|Path|null $scopes
     */
    public function __construct(
        string|Path|null $name = null,
        string|Path|null $description = null,
        public array|Path|null $scopes = null,
        public string|Path|null $refreshUrl = null,
    ) {
        parent::__construct(
            name: $name,
            description: $description,
        );
    }
}

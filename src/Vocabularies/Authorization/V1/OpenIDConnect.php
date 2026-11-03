<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Authorization\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class OpenIDConnect extends Authorization
{
    public const string TYPE = 'Org.OData.Authorization.V1.OpenIDConnect';

    public const array FIELDS = [
        'Name' => ['name', 'Edm.String', true],
        'Description' => ['description', 'Edm.String', false],
        'IssuerUrl' => ['issuerUrl', 'Edm.String', true],
    ];

    /**
     * @param string|Path|null $name Name that can be used to reference the authorization scheme
     * @param string|Path|null $description Description of the authorization scheme
     * @param string|Path|null $issuerUrl Issuer location for the OpenID Provider. Configuration information can be obtained by appending `/.well-known/openid-configuration` to this Url.
     */
    public function __construct(
        string|Path|null $name = null,
        string|Path|null $description = null,
        public string|Path|null $issuerUrl = null,
    ) {
        parent::__construct(
            name: $name,
            description: $description,
        );
    }
}

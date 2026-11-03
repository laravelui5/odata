<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Authorization\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class Http extends Authorization
{
    public const string TYPE = 'Org.OData.Authorization.V1.Http';

    public const array FIELDS = [
        'Name' => ['name', 'Edm.String', true],
        'Description' => ['description', 'Edm.String', false],
        'Scheme' => ['scheme', 'Edm.String', true],
        'BearerFormat' => ['bearerFormat', 'Edm.String', false],
    ];

    /**
     * @param string|Path|null $name Name that can be used to reference the authorization scheme
     * @param string|Path|null $description Description of the authorization scheme
     * @param string|Path|null $scheme HTTP Authorization scheme to be used in the Authorization header, as per RFC7235
     * @param string|Path|null $bearerFormat Format of the bearer token
     */
    public function __construct(
        string|Path|null $name = null,
        string|Path|null $description = null,
        public string|Path|null $scheme = null,
        public string|Path|null $bearerFormat = null,
    ) {
        parent::__construct(
            name: $name,
            description: $description,
        );
    }
}

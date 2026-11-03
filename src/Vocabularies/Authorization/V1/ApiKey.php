<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Authorization\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ApiKey extends Authorization
{
    public const string TYPE = 'Org.OData.Authorization.V1.ApiKey';

    public const array FIELDS = [
        'Name' => ['name', 'Edm.String', true],
        'Description' => ['description', 'Edm.String', false],
        'KeyName' => ['keyName', 'Edm.String', true],
        'Location' => ['location', 'Org.OData.Authorization.V1.KeyLocation', true],
    ];

    /**
     * @param string|Path|null $name Name that can be used to reference the authorization scheme
     * @param string|Path|null $description Description of the authorization scheme
     * @param string|Path|null $keyName The name of the header or query parameter
     * @param KeyLocation|Path|null $location Whether the API Key is passed in the header or as a query option
     */
    public function __construct(
        string|Path|null $name = null,
        string|Path|null $description = null,
        public string|Path|null $keyName = null,
        public KeyLocation|Path|null $location = null,
    ) {
        parent::__construct(
            name: $name,
            description: $description,
        );
    }
}

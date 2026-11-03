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

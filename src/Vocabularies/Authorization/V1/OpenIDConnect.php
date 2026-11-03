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

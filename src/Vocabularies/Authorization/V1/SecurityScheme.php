<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Authorization\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class SecurityScheme extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Authorization.V1.SecurityScheme';

    public const array FIELDS = [
        'Authorization' => ['authorization', 'Edm.String', true],
        'RequiredScopes' => ['requiredScopes', 'Collection(Edm.String)', false],
    ];

    /**
     * @param string|Path|null $authorization The name of a required authorization scheme
     * @param list<string>|Path|null $requiredScopes The names of scopes required from this authorization scheme
     */
    public function __construct(
        public string|Path|null $authorization = null,
        public array|Path|null $requiredScopes = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Authorization\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class AuthorizationScope extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Authorization.V1.AuthorizationScope';

    public const array FIELDS = [
        'Scope' => ['scope', 'Edm.String', true],
        'Grant' => ['grant', 'Edm.String', false],
        'Description' => ['description', 'Edm.String', true],
    ];

    public function __construct(
        public string|Path|null $scope = null,
        public string|Path|null $grant = null,
        public string|Path|null $description = null,
    ) {}
}

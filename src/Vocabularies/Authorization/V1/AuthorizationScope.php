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

    /**
     * @param string|Path|null $scope Scope name
     * @param string|Path|null $grant Identity that has access to the scope or can grant access to the scope.
     * @param string|Path|null $description Description of the scope
     */
    public function __construct(
        public string|Path|null $scope = null,
        public string|Path|null $grant = null,
        public string|Path|null $description = null,
    ) {}
}

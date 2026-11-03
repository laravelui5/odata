<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ScopeType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.ScopeType';

    public const array FIELDS = [
        'Scope' => ['scope', 'Edm.String', true],
        'RestrictedProperties' => ['restrictedProperties', 'Edm.String', false],
    ];

    /**
     * @param string|Path|null $scope Name of the scope.
     * @param string|Path|null $restrictedProperties Comma-separated string value of all properties that will be included or excluded when using the scope.
     */
    public function __construct(
        public string|Path|null $scope = null,
        public string|Path|null $restrictedProperties = null,
    ) {}
}

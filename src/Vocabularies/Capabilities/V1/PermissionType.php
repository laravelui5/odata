<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class PermissionType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.PermissionType';

    public const array FIELDS = [
        'SchemeName' => ['schemeName', 'Edm.String', true],
        'Scopes' => ['scopes', 'Collection(Org.OData.Capabilities.V1.ScopeType)', false],
    ];

    /**
     * @param list<ScopeType>|Path|null $scopes
     */
    public function __construct(
        public string|Path|null $schemeName = null,
        public array|Path|null $scopes = null,
    ) {}
}

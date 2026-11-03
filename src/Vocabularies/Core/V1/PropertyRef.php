<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class PropertyRef extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Core.V1.PropertyRef';

    public const array FIELDS = [
        'Name' => ['name', 'Edm.PropertyPath', true],
        'Alias' => ['alias', 'Edm.String', false],
    ];

    /**
     * @param string|Path|null $name A path expression resolving to a primitive property of the entity type itself or to a primitive property of a complex or navigation property (recursively) of the entity type. The names of the properties in the path are joined together by forward slashes.
     * @param string|Path|null $alias A SimpleIdentifier that MUST be unique within the set of aliases, structural and navigation properties of the containing entity type that MUST be used in the key predicate of URLs
     */
    public function __construct(
        public string|Path|null $name = null,
        public string|Path|null $alias = null,
    ) {}
}

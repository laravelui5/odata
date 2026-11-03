<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Aggregation\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class RecursiveHierarchyType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Aggregation.V1.RecursiveHierarchyType';

    public const array FIELDS = [
        'NodeProperty' => ['nodeProperty', 'Edm.PropertyPath', true],
        'ParentNavigationProperty' => ['parentNavigationProperty', 'Edm.NavigationPropertyPath', true],
    ];

    /**
     * @param string|Path|null $nodeProperty Primitive property holding the node identifier
     * @param string|Path|null $parentNavigationProperty Property for navigating to the parent node(s). Its type MUST be the entity type annotated with this term, and it MUST be collection-valued or nullable single-valued.
     */
    public function __construct(
        public string|Path|null $nodeProperty = null,
        public string|Path|null $parentNavigationProperty = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class RecursiveHierarchyType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Common.v1.RecursiveHierarchyType';

    public const array FIELDS = [
        'ExternalNodeKeyProperty' => ['externalNodeKeyProperty', 'Edm.PropertyPath', false],
        'NodeDescendantCountProperty' => ['nodeDescendantCountProperty', 'Edm.PropertyPath', false],
        'NodeDrillStateProperty' => ['nodeDrillStateProperty', 'Edm.PropertyPath', false],
    ];

    /**
     * @param string|Path|null $externalNodeKeyProperty Property holding the external human-readable key identifying the node
     * @param string|Path|null $nodeDescendantCountProperty Property holding the descendant count for a hierarchy node. The descendant count of a node is the number of its descendants in the hierarchy structure of the result considering only those nodes matching any specified $filter and $search. A property holding descendant counts has an integer data type.
     * @param string|Path|null $nodeDrillStateProperty Property holding the drill state of a hierarchy node. The drill state is indicated by one of the following string values: collapsed, expanded, or leaf. For an expanded node, its children are included in the result collection. For a collapsed node, the children are included in the entity set, but they are not part of the result collection. Retrieving them requires a relaxed filter expression or a separate request filtering on the parent node ID with the ID of the collapsed node. A leaf does not have any child in the entity set.
     */
    public function __construct(
        public string|Path|null $externalNodeKeyProperty = null,
        public string|Path|null $nodeDescendantCountProperty = null,
        public string|Path|null $nodeDrillStateProperty = null,
    ) {}
}

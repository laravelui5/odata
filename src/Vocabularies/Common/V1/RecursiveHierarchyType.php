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

    public function __construct(
        public string|Path|null $externalNodeKeyProperty = null,
        public string|Path|null $nodeDescendantCountProperty = null,
        public string|Path|null $nodeDrillStateProperty = null,
    ) {}
}

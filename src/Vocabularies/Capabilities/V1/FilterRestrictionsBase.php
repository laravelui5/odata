<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

readonly class FilterRestrictionsBase extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.FilterRestrictionsBase';

    public const array FIELDS = [
        'Filterable' => ['filterable', 'Edm.Boolean', false],
        'RequiresFilter' => ['requiresFilter', 'Edm.Boolean', false],
        'MaxLevels' => ['maxLevels', 'Edm.Int32', false],
    ];

    /**
     * @param bool|Path|null $filterable $filter is supported
     * @param bool|Path|null $requiresFilter $filter is required
     * @param int|Path|null $maxLevels The maximum number of levels (including recursion) that can be traversed in a filter expression. A value of -1 indicates there is no restriction.
     */
    public function __construct(
        public bool|Path|null $filterable = null,
        public bool|Path|null $requiresFilter = null,
        public int|Path|null $maxLevels = null,
    ) {}
}

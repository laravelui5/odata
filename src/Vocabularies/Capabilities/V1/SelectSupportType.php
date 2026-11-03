<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class SelectSupportType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.SelectSupportType';

    public const array FIELDS = [
        'Supported' => ['supported', 'Edm.Boolean', false],
        'InstanceAnnotationsSupported' => ['instanceAnnotationsSupported', 'Edm.Boolean', false],
        'Expandable' => ['expandable', 'Edm.Boolean', false],
        'Filterable' => ['filterable', 'Edm.Boolean', false],
        'Searchable' => ['searchable', 'Edm.Boolean', false],
        'TopSupported' => ['topSupported', 'Edm.Boolean', false],
        'SkipSupported' => ['skipSupported', 'Edm.Boolean', false],
        'ComputeSupported' => ['computeSupported', 'Edm.Boolean', false],
        'Countable' => ['countable', 'Edm.Boolean', false],
        'Sortable' => ['sortable', 'Edm.Boolean', false],
    ];

    /**
     * @param bool|Path|null $supported Supports $select
     * @param bool|Path|null $instanceAnnotationsSupported Supports instance annotations in $select list
     * @param bool|Path|null $expandable $expand within $select is supported
     * @param bool|Path|null $filterable $filter within $select is supported
     * @param bool|Path|null $searchable $search within $select is supported
     * @param bool|Path|null $topSupported $top within $select is supported
     * @param bool|Path|null $skipSupported $skip within $select is supported
     * @param bool|Path|null $computeSupported $compute within $select is supported
     * @param bool|Path|null $countable $count within $select is supported
     * @param bool|Path|null $sortable $orderby within $select is supported
     */
    public function __construct(
        public bool|Path|null $supported = null,
        public bool|Path|null $instanceAnnotationsSupported = null,
        public bool|Path|null $expandable = null,
        public bool|Path|null $filterable = null,
        public bool|Path|null $searchable = null,
        public bool|Path|null $topSupported = null,
        public bool|Path|null $skipSupported = null,
        public bool|Path|null $computeSupported = null,
        public bool|Path|null $countable = null,
        public bool|Path|null $sortable = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ModificationQueryOptionsType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.ModificationQueryOptionsType';

    public const array FIELDS = [
        'ExpandSupported' => ['expandSupported', 'Edm.Boolean', false],
        'SelectSupported' => ['selectSupported', 'Edm.Boolean', false],
        'ComputeSupported' => ['computeSupported', 'Edm.Boolean', false],
        'FilterSupported' => ['filterSupported', 'Edm.Boolean', false],
        'SearchSupported' => ['searchSupported', 'Edm.Boolean', false],
        'SortSupported' => ['sortSupported', 'Edm.Boolean', false],
    ];

    public function __construct(
        public bool|Path|null $expandSupported = null,
        public bool|Path|null $selectSupported = null,
        public bool|Path|null $computeSupported = null,
        public bool|Path|null $filterSupported = null,
        public bool|Path|null $searchSupported = null,
        public bool|Path|null $sortSupported = null,
    ) {}
}

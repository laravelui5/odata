<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Aggregation\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

readonly class ApplySupportedBase extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Aggregation.V1.ApplySupportedBase';

    public const array FIELDS = [
        'Transformations' => ['transformations', 'Collection(Edm.String)', false],
        'CustomAggregationMethods' => ['customAggregationMethods', 'Collection(Edm.String)', false],
        'Rollup' => ['rollup', 'Org.OData.Aggregation.V1.RollupType', false],
        'From' => ['from', 'Edm.Boolean', false],
    ];

    /**
     * @param list<string>|Path|null $transformations
     * @param list<string>|Path|null $customAggregationMethods
     */
    public function __construct(
        public array|Path|null $transformations = null,
        public array|Path|null $customAggregationMethods = null,
        public RollupType|Path|null $rollup = null,
        public bool|Path|null $from = null,
    ) {}
}

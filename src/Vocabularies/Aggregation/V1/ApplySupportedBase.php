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
     * @param list<string>|Path|null $transformations Transformations that can be used in `$apply`
     * @param list<string>|Path|null $customAggregationMethods Qualified names of custom aggregation methods that can be used in `aggregate(...with...)`
     * @param RollupType|Path|null $rollup The service supports rollup hierarchies in a `groupby` transformation
     * @param bool|Path|null $from The service supports the `from` keyword in an `aggregate` transformation
     */
    public function __construct(
        public array|Path|null $transformations = null,
        public array|Path|null $customAggregationMethods = null,
        public RollupType|Path|null $rollup = null,
        public bool|Path|null $from = null,
    ) {}
}

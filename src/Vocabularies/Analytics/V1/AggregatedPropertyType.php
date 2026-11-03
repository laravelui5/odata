<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Analytics\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class AggregatedPropertyType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Analytics.v1.AggregatedPropertyType';

    public const array FIELDS = [
        'Name' => ['name', 'Edm.String', true],
        'AggregationMethod' => ['aggregationMethod', 'Edm.String', true],
        'AggregatableProperty' => ['aggregatableProperty', 'Edm.PropertyPath', true],
    ];

    /**
     * @param string|Path|null $name Name of the dynamic property holding the aggregated value.
     * @param string|Path|null $aggregationMethod Name of the standard or custom aggregation method to be applied.
     * @param string|Path|null $aggregatableProperty Property whose values shall be aggregated.
     */
    public function __construct(
        public string|Path|null $name = null,
        public string|Path|null $aggregationMethod = null,
        public string|Path|null $aggregatableProperty = null,
    ) {}
}

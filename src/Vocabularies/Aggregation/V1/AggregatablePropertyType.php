<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Aggregation\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class AggregatablePropertyType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Aggregation.V1.AggregatablePropertyType';

    public const array FIELDS = [
        'Property' => ['property', 'Edm.PropertyPath', true],
        'SupportedAggregationMethods' => ['supportedAggregationMethods', 'Collection(Edm.String)', false],
        'RecommendedAggregationMethod' => ['recommendedAggregationMethod', 'Edm.String', false],
    ];

    /**
     * @param string|Path|null $property Aggregatable property
     * @param list<string>|Path|null $supportedAggregationMethods Standard and custom aggregation methods that can be applied to the property. If omitted, all aggregation methods can be applied
     * @param string|Path|null $recommendedAggregationMethod Recommended method for aggregating values of the property
     */
    public function __construct(
        public string|Path|null $property = null,
        public array|Path|null $supportedAggregationMethods = null,
        public string|Path|null $recommendedAggregationMethod = null,
    ) {}
}

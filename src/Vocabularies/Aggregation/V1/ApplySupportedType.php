<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Aggregation\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ApplySupportedType extends ApplySupportedBase
{
    public const string TYPE = 'Org.OData.Aggregation.V1.ApplySupportedType';

    public const array FIELDS = [
        'Transformations' => ['transformations', 'Collection(Edm.String)', false],
        'CustomAggregationMethods' => ['customAggregationMethods', 'Collection(Edm.String)', false],
        'Rollup' => ['rollup', 'Org.OData.Aggregation.V1.RollupType', false],
        'From' => ['from', 'Edm.Boolean', false],
        'PropertyRestrictions' => ['propertyRestrictions', 'Edm.Boolean', false],
        'GroupableProperties' => ['groupableProperties', 'Collection(Edm.AnyPropertyPath)', false],
        'AggregatableProperties' => ['aggregatableProperties', 'Collection(Org.OData.Aggregation.V1.AggregatablePropertyType)', false],
    ];

    /**
     * @param list<string>|Path|null $transformations Transformations that can be used in `$apply`
     * @param list<string>|Path|null $customAggregationMethods Qualified names of custom aggregation methods that can be used in `aggregate(...with...)`
     * @param RollupType|Path|null $rollup The service supports rollup hierarchies in a `groupby` transformation
     * @param bool|Path|null $from The service supports the `from` keyword in an `aggregate` transformation
     * @param bool|Path|null $propertyRestrictions Only properties marked as `Groupable` can be used in the `groupby` transformation, and only those marked as `Aggregatable` can be used in the `aggregate` transformation
     * @param list<mixed>|Path|null $groupableProperties A non-empty collection indicates that only the listed properties of the annotated target are supported by the `groupby` transformation
     * @param list<AggregatablePropertyType>|Path|null $aggregatableProperties A non-empty collection indicates that only the listed properties of the annotated target can be used in the `aggregate` transformation, optionally restricted to the specified aggregation methods
     */
    public function __construct(
        array|Path|null $transformations = null,
        array|Path|null $customAggregationMethods = null,
        RollupType|Path|null $rollup = null,
        bool|Path|null $from = null,
        public bool|Path|null $propertyRestrictions = null,
        public array|Path|null $groupableProperties = null,
        public array|Path|null $aggregatableProperties = null,
    ) {
        parent::__construct(
            transformations: $transformations,
            customAggregationMethods: $customAggregationMethods,
            rollup: $rollup,
            from: $from,
        );
    }
}

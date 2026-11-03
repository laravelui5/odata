<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class FilterRestrictionsType extends FilterRestrictionsBase
{
    public const string TYPE = 'Org.OData.Capabilities.V1.FilterRestrictionsType';

    public const array FIELDS = [
        'Filterable' => ['filterable', 'Edm.Boolean', false],
        'RequiresFilter' => ['requiresFilter', 'Edm.Boolean', false],
        'MaxLevels' => ['maxLevels', 'Edm.Int32', false],
        'RequiredProperties' => ['requiredProperties', 'Collection(Edm.PropertyPath)', false],
        'NonFilterableProperties' => ['nonFilterableProperties', 'Collection(Edm.PropertyPath)', false],
        'FilterExpressionRestrictions' => ['filterExpressionRestrictions', 'Collection(Org.OData.Capabilities.V1.FilterExpressionRestrictionType)', false],
    ];

    /**
     * @param bool|Path|null $filterable $filter is supported
     * @param bool|Path|null $requiresFilter $filter is required
     * @param int|Path|null $maxLevels The maximum number of levels (including recursion) that can be traversed in a filter expression. A value of -1 indicates there is no restriction.
     * @param list<string>|Path|null $requiredProperties These properties must be specified in the $filter clause (properties of derived types are not allowed here)
     * @param list<string>|Path|null $nonFilterableProperties These structural properties cannot be used in filter expressions
     * @param list<FilterExpressionRestrictionType>|Path|null $filterExpressionRestrictions These properties only allow a subset of filter expressions. A valid filter expression for a single property can be enclosed in parentheses and combined by `and` with valid expressions for other properties.
     */
    public function __construct(
        bool|Path|null $filterable = null,
        bool|Path|null $requiresFilter = null,
        int|Path|null $maxLevels = null,
        public array|Path|null $requiredProperties = null,
        public array|Path|null $nonFilterableProperties = null,
        public array|Path|null $filterExpressionRestrictions = null,
    ) {
        parent::__construct(
            filterable: $filterable,
            requiresFilter: $requiresFilter,
            maxLevels: $maxLevels,
        );
    }
}

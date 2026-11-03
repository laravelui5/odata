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
     * @param list<string>|Path|null $requiredProperties
     * @param list<string>|Path|null $nonFilterableProperties
     * @param list<FilterExpressionRestrictionType>|Path|null $filterExpressionRestrictions
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

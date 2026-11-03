<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class SortRestrictionsType extends SortRestrictionsBase
{
    public const string TYPE = 'Org.OData.Capabilities.V1.SortRestrictionsType';

    public const array FIELDS = [
        'Sortable' => ['sortable', 'Edm.Boolean', false],
        'AscendingOnlyProperties' => ['ascendingOnlyProperties', 'Collection(Edm.PropertyPath)', false],
        'DescendingOnlyProperties' => ['descendingOnlyProperties', 'Collection(Edm.PropertyPath)', false],
        'NonSortableProperties' => ['nonSortableProperties', 'Collection(Edm.PropertyPath)', false],
    ];

    /**
     * @param list<string>|Path|null $ascendingOnlyProperties
     * @param list<string>|Path|null $descendingOnlyProperties
     * @param list<string>|Path|null $nonSortableProperties
     */
    public function __construct(
        bool|Path|null $sortable = null,
        public array|Path|null $ascendingOnlyProperties = null,
        public array|Path|null $descendingOnlyProperties = null,
        public array|Path|null $nonSortableProperties = null,
    ) {
        parent::__construct(
            sortable: $sortable,
        );
    }
}

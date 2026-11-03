<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ExpandRestrictionsType extends ExpandCollectionRestrictionsType
{
    public const string TYPE = 'Org.OData.Capabilities.V1.ExpandRestrictionsType';

    public const array FIELDS = [
        'Expandable' => ['expandable', 'Edm.Boolean', false],
        'StreamsExpandable' => ['streamsExpandable', 'Edm.Boolean', false],
        'MaxLevels' => ['maxLevels', 'Edm.Int32', false],
        'ExpandByKeyRestrictions' => ['expandByKeyRestrictions', 'Org.OData.Capabilities.V1.ExpandByKeyRestrictionsBase', false],
        'NonExpandableProperties' => ['nonExpandableProperties', 'Collection(Edm.NavigationPropertyPath)', false],
        'NonExpandableStreamProperties' => ['nonExpandableStreamProperties', 'Collection(Edm.PropertyPath)', false],
    ];

    /**
     * @param list<string>|Path|null $nonExpandableProperties
     * @param list<string>|Path|null $nonExpandableStreamProperties
     */
    public function __construct(
        bool|Path|null $expandable = null,
        bool|Path|null $streamsExpandable = null,
        int|Path|null $maxLevels = null,
        ExpandByKeyRestrictionsBase|Path|null $expandByKeyRestrictions = null,
        public array|Path|null $nonExpandableProperties = null,
        public array|Path|null $nonExpandableStreamProperties = null,
    ) {
        parent::__construct(
            expandable: $expandable,
            streamsExpandable: $streamsExpandable,
            maxLevels: $maxLevels,
            expandByKeyRestrictions: $expandByKeyRestrictions,
        );
    }
}

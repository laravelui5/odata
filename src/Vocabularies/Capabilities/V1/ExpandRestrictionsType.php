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
     * @param bool|Path|null $expandable $expand is supported
     * @param bool|Path|null $streamsExpandable $expand is supported for stream properties and media streams
     * @param int|Path|null $maxLevels The maximum number of levels that can be expanded in a expand expression. A value of -1 indicates there is no restriction.
     * @param ExpandByKeyRestrictionsBase|Path|null $expandByKeyRestrictions Restrictions on expand expressions when accessed by key
     * @param list<string>|Path|null $nonExpandableProperties These properties cannot be used in expand expressions
     * @param list<string>|Path|null $nonExpandableStreamProperties These stream properties cannot be used in expand expressions
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

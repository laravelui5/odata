<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

readonly class ExpandCollectionRestrictionsType extends ExpandRestrictionsBase
{
    public const string TYPE = 'Org.OData.Capabilities.V1.ExpandCollectionRestrictionsType';

    public const array FIELDS = [
        'Expandable' => ['expandable', 'Edm.Boolean', false],
        'StreamsExpandable' => ['streamsExpandable', 'Edm.Boolean', false],
        'MaxLevels' => ['maxLevels', 'Edm.Int32', false],
        'ExpandByKeyRestrictions' => ['expandByKeyRestrictions', 'Org.OData.Capabilities.V1.ExpandByKeyRestrictionsBase', false],
    ];

    /**
     * @param bool|Path|null $expandable $expand is supported
     * @param bool|Path|null $streamsExpandable $expand is supported for stream properties and media streams
     * @param int|Path|null $maxLevels The maximum number of levels that can be expanded in a expand expression. A value of -1 indicates there is no restriction.
     * @param ExpandByKeyRestrictionsBase|Path|null $expandByKeyRestrictions Restrictions on expand expressions when accessed by key
     */
    public function __construct(
        bool|Path|null $expandable = null,
        bool|Path|null $streamsExpandable = null,
        int|Path|null $maxLevels = null,
        public ExpandByKeyRestrictionsBase|Path|null $expandByKeyRestrictions = null,
    ) {
        parent::__construct(
            expandable: $expandable,
            streamsExpandable: $streamsExpandable,
            maxLevels: $maxLevels,
        );
    }
}

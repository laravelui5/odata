<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

readonly class ExpandRestrictionsBase extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.ExpandRestrictionsBase';

    public const array FIELDS = [
        'Expandable' => ['expandable', 'Edm.Boolean', false],
        'StreamsExpandable' => ['streamsExpandable', 'Edm.Boolean', false],
        'MaxLevels' => ['maxLevels', 'Edm.Int32', false],
    ];

    /**
     * @param bool|Path|null $expandable $expand is supported
     * @param bool|Path|null $streamsExpandable $expand is supported for stream properties and media streams
     * @param int|Path|null $maxLevels The maximum number of levels that can be expanded in a expand expression. A value of -1 indicates there is no restriction.
     */
    public function __construct(
        public bool|Path|null $expandable = null,
        public bool|Path|null $streamsExpandable = null,
        public int|Path|null $maxLevels = null,
    ) {}
}

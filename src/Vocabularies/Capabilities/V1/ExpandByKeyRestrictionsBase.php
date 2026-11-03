<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

readonly class ExpandByKeyRestrictionsBase extends ExpandRestrictionsBase
{
    public const string TYPE = 'Org.OData.Capabilities.V1.ExpandByKeyRestrictionsBase';

    public const array FIELDS = [
        'Expandable' => ['expandable', 'Edm.Boolean', false],
        'StreamsExpandable' => ['streamsExpandable', 'Edm.Boolean', false],
        'MaxLevels' => ['maxLevels', 'Edm.Int32', false],
    ];
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

readonly class SortRestrictionsBase extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.SortRestrictionsBase';

    public const array FIELDS = [
        'Sortable' => ['sortable', 'Edm.Boolean', false],
    ];

    /**
     * @param bool|Path|null $sortable $orderby is supported
     */
    public function __construct(
        public bool|Path|null $sortable = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

readonly class CountRestrictionsBase extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.CountRestrictionsBase';

    public const array FIELDS = [
        'Countable' => ['countable', 'Edm.Boolean', false],
    ];

    /**
     * @param bool|Path|null $countable Instances can be counted in requests targeting a collection
     */
    public function __construct(
        public bool|Path|null $countable = null,
    ) {}
}

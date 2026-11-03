<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Aggregation\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class CustomAggregateType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Aggregation.V1.CustomAggregateType';

    public const array FIELDS = [
        'Name' => ['name', 'Edm.String', true],
        'Type' => ['type', 'Edm.String', true],
    ];

    /**
     * @param string|Path|null $name Name of the dynamic property that can be used in the `aggregate` transformation
     * @param string|Path|null $type Qualified name of a primitive type. The aggregated value will be of that type
     */
    public function __construct(
        public string|Path|null $name = null,
        public string|Path|null $type = null,
    ) {}
}

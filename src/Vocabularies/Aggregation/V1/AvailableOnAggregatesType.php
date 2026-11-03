<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Aggregation\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class AvailableOnAggregatesType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Aggregation.V1.AvailableOnAggregatesType';

    public const array FIELDS = [
        'RequiredProperties' => ['requiredProperties', 'Collection(Edm.PropertyPath)', false],
    ];

    /**
     * @param list<string>|Path|null $requiredProperties
     */
    public function __construct(
        public array|Path|null $requiredProperties = null,
    ) {}
}

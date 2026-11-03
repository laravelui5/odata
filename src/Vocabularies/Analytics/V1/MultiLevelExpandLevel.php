<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Analytics\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Property names constituting a level in an [unnamed leveled hierarchy](#MultiLevelExpand)
 */
final readonly class MultiLevelExpandLevel extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Analytics.v1.MultiLevelExpandLevel';

    public const array FIELDS = [
        'DimensionProperties' => ['dimensionProperties', 'Collection(Edm.String)', false],
        'AdditionalProperties' => ['additionalProperties', 'Collection(Edm.String)', false],
    ];

    /**
     * @param list<string>|Path|null $dimensionProperties
     * @param list<string>|Path|null $additionalProperties
     */
    public function __construct(
        public array|Path|null $dimensionProperties = null,
        public array|Path|null $additionalProperties = null,
    ) {}
}

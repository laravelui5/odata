<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Analytics\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Expansion state of an entry in an [unnamed leveled hierarchy](#MultiLevelExpand)
 */
final readonly class MultiLevelExpandEntry extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Analytics.v1.MultiLevelExpandEntry';

    public const array FIELDS = [
        'Entry' => ['entry', 'Collection(Edm.String)', false],
        'Levels' => ['levels', 'Edm.Int64', false],
    ];

    /**
     * @param list<string>|Path|null $entry An entry on a given [level](#MultiLevelExpandLevel) is identified by a list of values for the `DimensionProperties` that constitute all levels up to and including the given one
     * @param int|Path|null $levels Number of levels to be expanded, null means all levels, 0 means collapsed
     */
    public function __construct(
        public array|Path|null $entry = null,
        public int|Path|null $levels = null,
    ) {}
}

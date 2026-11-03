<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Analytics\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Sibling order in an [unnamed leveled hierarchy](#MultiLevelExpand)
 */
final readonly class MultiLevelExpandSiblingOrder extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Analytics.v1.MultiLevelExpandSiblingOrder';

    public const array FIELDS = [
        'Property' => ['property', 'Edm.String', true],
        'Descending' => ['descending', 'Edm.Boolean', false],
    ];

    /**
     * @param string|Path|null $property Property by which to sort
     * @param bool|Path|null $descending Sort direction, ascending if not specified otherwise
     */
    public function __construct(
        public string|Path|null $property = null,
        public bool|Path|null $descending = null,
    ) {}
}

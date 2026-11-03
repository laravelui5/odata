<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * List of value ranges for a single property
 */
final readonly class SelectOptionType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.SelectOptionType';

    public const array FIELDS = [
        'PropertyName' => ['propertyName', 'Edm.PropertyPath', false],
        'DynamicPropertyName' => ['dynamicPropertyName', 'Edm.AnnotationPath', false],
        'Ranges' => ['ranges', 'Collection(com.sap.vocabularies.UI.v1.SelectionRangeType)', false],
    ];

    /**
     * @param list<SelectionRangeType>|Path|null $ranges
     */
    public function __construct(
        public string|Path|null $propertyName = null,
        public string|Path|null $dynamicPropertyName = null,
        public array|Path|null $ranges = null,
    ) {}
}

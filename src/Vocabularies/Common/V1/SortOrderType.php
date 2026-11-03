<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Exactly one of `Property`, `DynamicProperty` and `Expression` must be present
 */
final readonly class SortOrderType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Common.v1.SortOrderType';

    public const array FIELDS = [
        'Property' => ['property', 'Edm.PropertyPath', false],
        'DynamicProperty' => ['dynamicProperty', 'Edm.AnnotationPath', false],
        'Expression' => ['expression', 'Edm.PrimitiveType', false],
        'Descending' => ['descending', 'Edm.Boolean', false],
    ];

    public function __construct(
        public string|Path|null $property = null,
        public string|Path|null $dynamicProperty = null,
        public mixed $expression = null,
        public bool|Path|null $descending = null,
    ) {}
}

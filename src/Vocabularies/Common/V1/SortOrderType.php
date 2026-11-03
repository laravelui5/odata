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

    /**
     * @param string|Path|null $property Sort property
     * @param string|Path|null $dynamicProperty Dynamic property introduced by an annotation and used as sort property
     * @param mixed $expression Dynamic expression whose primitive result value is used to sort the instances
     * @param bool|Path|null $descending Sort direction, ascending if not specified otherwise
     */
    public function __construct(
        public string|Path|null $property = null,
        public string|Path|null $dynamicProperty = null,
        public mixed $expression = null,
        public bool|Path|null $descending = null,
    ) {}
}

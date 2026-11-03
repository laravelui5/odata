<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * A record that behaves like the standard referential constraint on the navigation property targeted by a [`ReferentialConstraint`](#ReferentialConstraint) annotation,
          but the nullability requirement for the dependent property is lifted.
          It asserts that the principal property _of an existing related entity_
          must have the same value as the dependent property.
 */
final readonly class ReferentialConstraintType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Common.v1.ReferentialConstraintType';

    public const array FIELDS = [
        'Property' => ['property', 'Edm.PropertyPath', true],
        'ReferencedProperty' => ['referencedProperty', 'Edm.PropertyPath', true],
    ];

    /**
     * @param string|Path|null $property The path to the property is evaluated relative to the type containing the navigation property
     * @param string|Path|null $referencedProperty The path to the referenced property MUST start with a segment containing the navigation property
     */
    public function __construct(
        public string|Path|null $property = null,
        public string|Path|null $referencedProperty = null,
    ) {}
}

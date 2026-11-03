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

    public function __construct(
        public string|Path|null $property = null,
        public string|Path|null $referencedProperty = null,
    ) {}
}

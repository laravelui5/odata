<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

/**
 * A record that behaves like the standard referential constraint on the navigation property targeted by a [`ReferentialConstraint`](#ReferentialConstraint) annotation,
          but the nullability requirement for the dependent property is lifted.
          It asserts that the principal property _of an existing related entity_
          must have the same value as the dependent property.
 */
final readonly class ReferentialConstraintType
{
    public function __construct(
        public readonly string $property,
        public readonly string $referencedProperty,
    ) {}
}

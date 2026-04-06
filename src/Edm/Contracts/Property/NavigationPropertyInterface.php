<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Property;

use LaravelUi5\OData\Edm\Contracts\AnnotatableInterface;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\NamedElementInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;

/**
 * A navigation property, representing an association between two
 * entity types.
 *
 * Unlike structural properties, navigation properties always point
 * to an EntityType. They may be single-valued or collection-valued,
 * may declare a partner on the target side, and may declare
 * referential constraints that tie foreign-key structural properties
 * to the principal key.
 *
 * @see OData CSDL XML v4.01 §8 (Navigation Property)
 */
interface NavigationPropertyInterface extends NamedElementInterface, AnnotatableInterface, AnnotationTargetInterface
{
    /**
     * The resolved target entity type of this navigation property.
     *
     * @see OData CSDL XML v4.01 §8.1
     */
    public function getTargetType(): EntityTypeInterface;

    /**
     * Whether this navigation property is collection-valued.
     *
     * @see OData CSDL XML v4.01 §8.1
     */
    public function isCollection(): bool;

    /**
     * Whether a null target is permitted for a single-valued
     * navigation property. Always false for collection-valued
     * navigation properties.
     *
     * @see OData CSDL XML v4.01 §8.2
     */
    public function isNullable(): bool;

    /**
     * The name of the partner navigation property on the target
     * entity type, or null when no partner is declared.
     *
     * The partner is not resolved to an object here because doing so
     * would introduce a circular reference between the two types.
     * Callers can resolve the name via the target type if needed.
     *
     * @see OData CSDL XML v4.01 §8.3
     */
    public function getPartnerName(): ?string;

    /**
     * Whether this navigation property defines a containment
     * relationship, meaning the target entities are part of the
     * source entity and have no independent identity outside of it.
     *
     * @see OData CSDL XML v4.01 §8.4
     */
    public function isContainmentTarget(): bool;

    /**
     * The referential constraints declared on this navigation property,
     * mapping dependent structural property names to principal property
     * names. Returns an empty array when none are declared.
     *
     * The array key is the name of the dependent property on this
     * entity type; the value is the name of the referenced property
     * on the principal entity type.
     *
     * @return array<string, string>
     * @see OData CSDL XML v4.01 §8.5
     */
    public function getReferentialConstraints(): array;

    /**
     * The on-delete action declared for this relationship, or null
     * when absent. Possible values per spec: "Cascade", "None",
     * "SetNull", "SetDefault".
     *
     * @see OData CSDL XML v4.01 §8.6
     */
    public function getOnDeleteAction(): ?string;
}

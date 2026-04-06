<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Type;

use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;

/**
 * An entity type, the central construct of the OData Edm.
 *
 * Entity types represent things with identity — rows in a table,
 * documents in a collection. They carry a key (one or more
 * structural properties that uniquely identify an instance),
 * structural properties, and navigation properties that associate
 * them with other entity types.
 *
 * @see OData CSDL XML v4.01 §6 (Entity Type)
 */
interface EntityTypeInterface extends StructuredTypeInterface, AnnotationTargetInterface
{
    /**
     * The base entity type this type derives from, or null for
     * a root type.
     *
     * @see OData CSDL XML v4.01 §6.1
     */
    public function getBaseType(): ?EntityTypeInterface;

    /**
     * The properties that form the key of this entity type.
     *
     * For derived types the key is inherited from the root type;
     * this method returns that inherited key rather than an empty
     * collection. Returns an empty array only for abstract base
     * types that defer key declaration to their subtypes.
     *
     * The order of properties in the returned list is significant
     * and matches the CSDL declaration order.
     *
     * @return list<PropertyInterface>
     * @see OData CSDL XML v4.01 §6.5
     */
    public function getKey(): array;

    /**
     * All navigation properties declared directly on this type,
     * not including navigation properties inherited from a base type.
     *
     * @return list<NavigationPropertyInterface>
     */
    public function getDeclaredNavigationProperties(): array;

    /**
     * Returns a navigation property by name, searching this type
     * and all base types in the inheritance chain.
     *
     * Returns null when no navigation property with that name
     * exists anywhere in the hierarchy.
     */
    public function getNavigationProperty(string $name): ?NavigationPropertyInterface;

    /**
     * Whether this entity type is a media entity — i.e. it
     * represents a stream of binary data with associated metadata.
     *
     * @see OData CSDL XML v4.01 §6.4
     */
    public function hasStream(): bool;
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Type;

use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;

/**
 * A complex type — a structured value type without identity.
 *
 * Complex types are similar to entity types but carry no key. They
 * are used to group related structural properties into a reusable
 * value object, e.g. an address or a money amount. Unlike entity
 * types they can appear as the type of a structural property.
 *
 * Per OData v4.01 complex types may also carry navigation properties,
 * which is why this interface extends StructuredTypeInterface and
 * additionally exposes navigation property access.
 *
 * @see OData CSDL XML v4.01 §9 (Complex Type)
 */
interface ComplexTypeInterface extends StructuredTypeInterface, AnnotationTargetInterface
{
    /**
     * The base complex type this type derives from, or null for
     * a root type.
     *
     * @see OData CSDL XML v4.01 §9.1
     */
    public function getBaseType(): ?ComplexTypeInterface;

    /**
     * All navigation properties declared directly on this complex type.
     *
     * Navigation properties on complex types are permitted by the spec
     * but uncommon. This method returns an empty array for types that
     * declare none.
     *
     * @return list<NavigationPropertyInterface>
     */
    public function getDeclaredNavigationProperties(): array;

    /**
     * Returns a navigation property by name, searching this type
     * and all base types in the inheritance chain.
     */
    public function getNavigationProperty(string $name): ?NavigationPropertyInterface;
}

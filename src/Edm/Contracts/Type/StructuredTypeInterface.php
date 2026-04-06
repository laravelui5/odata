<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Type;

use LaravelUi5\OData\Edm\Contracts\AnnotatableInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;

/**
 * Common contract for structured types — entity types and complex
 * types — which hold a named set of structural properties.
 *
 * Structured types may derive from a base type of the same kind,
 * may be declared abstract, and may be open (permitting dynamic
 * properties beyond those declared in CSDL).
 *
 * @see OData CSDL XML v4.01 §3.2 (Structured Types)
 */
interface StructuredTypeInterface extends TypeInterface, AnnotatableInterface
{
    /**
     * All structural properties declared directly on this type,
     * not including properties inherited from a base type.
     *
     * @return list<PropertyInterface>
     */
    public function getDeclaredProperties(): array;

    /**
     * Returns a structural property by name, searching this type
     * and all base types in the inheritance chain.
     *
     * Returns null when no property with that name exists anywhere
     * in the hierarchy.
     */
    public function getProperty(string $name): ?PropertyInterface;

    /**
     * Whether this type is abstract and may not be instantiated
     * directly.
     */
    public function isAbstract(): bool;

    /**
     * Whether this type is open, permitting dynamic properties
     * whose names and types are not declared in CSDL.
     */
    public function isOpen(): bool;
}

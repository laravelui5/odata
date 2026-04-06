<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Type;

use LaravelUi5\OData\Edm\Contracts\NamedElementInterface;

/**
 * Root contract for every type that can appear as the resolved type
 * of a structural or navigation property.
 *
 * The four concrete subtypes of this interface are:
 *   - PrimitiveTypeInterface   (wraps a PrimitiveTypeEnum case)
 *   - EntityTypeInterface      (§6)
 *   - ComplexTypeInterface     (§9)
 *   - EnumTypeInterface        (§10)
 *   - TypeDefinitionInterface  (§11)
 *
 * Callers that need type-specific behaviour should use instanceof
 * checks or a visitor pattern rather than downcasting blindly.
 */
interface TypeInterface extends NamedElementInterface
{
    /**
     * The fully qualified name of this type including its namespace,
     * e.g. "MyService.Customer" or "Edm.String".
     */
    public function getQualifiedName(): string;
}

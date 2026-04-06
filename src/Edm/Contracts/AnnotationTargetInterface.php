<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts;

/**
 * Marker interface for every model element that is a valid annotation
 * target according to the OData CSDL specification.
 *
 * This interface has no methods. Its sole purpose is to form a closed
 * set of PHP types that may legally appear in the APPLIES_TO constant
 * of a TypedAnnotationInterface implementation. Static analysis tools
 * and the vocabulary generator use this marker to validate annotation
 * applicability at build time rather than at runtime.
 *
 * The following interfaces implement this marker:
 *   - EntityTypeInterface
 *   - ComplexTypeInterface
 *   - EntitySetInterface
 *   - SingletonInterface
 *   - PropertyInterface
 *   - NavigationPropertyInterface
 *   - FunctionInterface
 *   - FunctionParameterInterface
 *   - FunctionImportInterface
 *   - EnumTypeInterface
 *   - EnumMemberInterface
 *   - TypeDefinitionInterface
 *   - EntityContainerInterface
 *   - SchemaInterface
 *
 * @see OData CSDL XML v4.01 §14.1.2 (Applicability)
 */
interface AnnotationTargetInterface
{
}
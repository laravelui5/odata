<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts;

use LaravelUi5\OData\Edm\Contracts\Container\EnumTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\ComplexTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeDefinitionInterface;

/**
 * A CSDL schema — a namespace that groups a set of type definitions
 * and functions.
 *
 * A service's metadata document contains one or more schemas. Each
 * schema has a unique namespace and an optional alias. All named
 * elements within a schema are reachable by their simple name from
 * within the schema and by their qualified name from anywhere in scope.
 *
 * @see OData CSDL XML v4.01 §5 (Schema)
 */
interface SchemaInterface extends AnnotatableInterface, AnnotationTargetInterface
{
    /**
     * The full namespace of this schema, e.g. "MyService.Data".
     *
     * @see OData CSDL XML v4.01 §15.1
     */
    public function getNamespace(): string;

    /**
     * The optional alias for this schema's namespace, e.g. "self".
     *
     * @see OData CSDL XML v4.01 §5.1
     */
    public function getAlias(): ?string;

    /**
     * All entity types declared in this schema.
     *
     * @return list<EntityTypeInterface>
     */
    public function getEntityTypes(): array;

    /**
     * Returns an entity type by its simple (unqualified) name,
     * or null when not found.
     */
    public function getEntityType(string $name): ?EntityTypeInterface;

    /**
     * All complex types declared in this schema.
     *
     * @return list<ComplexTypeInterface>
     */
    public function getComplexTypes(): array;

    /**
     * Returns a complex type by its simple name, or null when not found.
     */
    public function getComplexType(string $name): ?ComplexTypeInterface;

    /**
     * All enum types declared in this schema.
     *
     * @return list<EnumTypeInterface>
     */
    public function getEnumTypes(): array;

    /**
     * Returns an enum type by its simple name, or null when not found.
     */
    public function getEnumType(string $name): ?EnumTypeInterface;

    /**
     * All type definitions declared in this schema.
     *
     * @return list<TypeDefinitionInterface>
     */
    public function getTypeDefinitions(): array;

    /**
     * Returns a type definition by its simple name, or null when
     * not found.
     */
    public function getTypeDefinition(string $name): ?TypeDefinitionInterface;

    /**
     * All function overloads declared in this schema, grouped by name.
     *
     * The array key is the simple function name; the value is a list
     * of all overloads with that name. Multiple overloads are possible
     * per the spec.
     *
     * @return array<string, list<FunctionInterface>>
     */
    public function getFunctions(): array;

    /**
     * Returns all overloads of the function with the given simple name,
     * or an empty array when no such function exists in this schema.
     *
     * @return list<FunctionInterface>
     */
    public function getFunction(string $name): array;
}

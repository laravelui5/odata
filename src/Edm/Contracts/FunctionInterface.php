<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts;

use LaravelUi5\OData\Edm\Contracts\Type\TypeInterface;

/**
 * A function overload as defined in CSDL.
 *
 * Functions are side-effect-free callables that return a value.
 * They may be bound to an entity type, entity set, singleton, or
 * the service root (unbound). Multiple overloads sharing the same
 * name may coexist; each overload is represented by a distinct
 * instance of this interface.
 *
 * Actions are explicitly out of scope for this read-only interface
 * layer.
 *
 * @see OData CSDL XML v4.01 §12.3 (Function)
 * @see OData CSDL XML v4.01 §12.4 (Function Overloads)
 */
interface FunctionInterface extends NamedElementInterface, AnnotatableInterface, AnnotationTargetInterface
{
    /**
     * Whether this overload is bound to a specific type or set.
     *
     * When true, the first parameter is the binding parameter and
     * constrains the context in which the function may be invoked.
     *
     * @see OData CSDL XML v4.01 §12.5
     */
    public function isBound(): bool;

    /**
     * Whether the result of this function may be further composed
     * with URL path segments, system query options, or operations.
     *
     * A composable function returning a collection can be followed
     * by $filter, $orderby, and similar; a composable function
     * returning a single entity can be followed by navigation
     * property segments.
     *
     * @see OData CSDL XML v4.01 §12.7
     */
    public function isComposable(): bool;

    /**
     * The return type of this function. Null only when the function
     * is declared without a return type, which the spec permits but
     * is unusual for well-formed services.
     *
     * @see OData CSDL XML v4.01 §12.8
     */
    public function getReturnType(): ?TypeInterface;

    /**
     * Whether the return type is a collection.
     */
    public function returnsCollection(): bool;

    /**
     * Whether a null return value is permitted.
     */
    public function isReturnTypeNullable(): bool;

    /**
     * All parameters of this function overload, in declaration order.
     *
     * For bound functions the binding parameter is at index 0.
     *
     * @return list<FunctionParameterInterface>
     * @see OData CSDL XML v4.01 §12.9
     */
    public function getParameters(): array;

    /**
     * Returns the parameter with the given name, or null if absent.
     */
    public function getParameter(string $name): ?FunctionParameterInterface;

    /**
     * For bound functions, the entity set path that identifies which
     * entity set in the container holds the return values, or null
     * when not applicable or not declared.
     *
     * Use the returned object's typed accessors to resolve the path
     * programmatically against the container's navigation property
     * bindings during query plan generation.
     *
     * @see OData CSDL XML v4.01 §12.6
     */
    public function getEntitySetPath(): ?EntitySetPathInterface;
}

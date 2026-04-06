<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts;

use LaravelUi5\OData\Edm\Contracts\Type\TypeFacetsInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeInterface;

/**
 * A single parameter of a function overload.
 *
 * Parameters are positional and named. The first parameter of a bound
 * function is the binding parameter, which constrains the type the
 * function may be called on.
 *
 * @see OData CSDL XML v4.01 §12.9 (Parameter)
 */
interface FunctionParameterInterface extends NamedElementInterface, AnnotatableInterface, AnnotationTargetInterface
{
    /**
     * The resolved type of this parameter. For collection parameters
     * this is the element type; use isCollection() to distinguish.
     */
    public function getType(): TypeInterface;

    /**
     * Whether this parameter accepts a collection of values.
     */
    public function isCollection(): bool;

    /**
     * Whether null is a permitted value for this parameter.
     * Defaults to true when not specified.
     */
    public function isNullable(): bool;

    /**
     * The facets constraining this parameter's type, or null when
     * none are declared.
     */
    public function getFacets(): ?TypeFacetsInterface;
}

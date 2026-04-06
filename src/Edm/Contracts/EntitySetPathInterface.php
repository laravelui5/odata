<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts;

/**
 * The entity set path of a bound function overload.
 *
 * When a bound function returns entities, the entity set path tells
 * the service which entity set in the container those entities belong
 * to. The path is always a two-segment expression: the name of the
 * binding parameter followed by the name of a navigation property on
 * the binding parameter's type.
 *
 * This interface exists to support machine resolution of the return
 * entity set during query plan generation: a planner can retrieve the
 * binding parameter type, navigate to the declared navigation property,
 * and from there resolve the target entity set via the container's
 * navigation property bindings.
 *
 * @see OData CSDL XML v4.01 §12.6 (Entity Set Path)
 */
interface EntitySetPathInterface
{
    /**
     * The name of the binding parameter, i.e. the first segment of
     * the path expression.
     *
     * This name corresponds to the first parameter of the function
     * overload as returned by FunctionInterface::getParameters().
     */
    public function getBindingParameterName(): string;

    /**
     * The name of the navigation property on the binding parameter's
     * type, i.e. the second segment of the path expression.
     *
     * Resolving this navigation property on the binding parameter's
     * entity type and then looking up the matching navigation property
     * binding on the function's container member yields the concrete
     * entity set that holds the function's return values.
     */
    public function getNavigationPropertyName(): string;

    /**
     * The full path expression as it appears in CSDL, e.g.
     * "bindingParameter/Orders".
     *
     * Provided as a convenience for serialisation and logging;
     * prefer the typed accessors above for programmatic resolution.
     */
    public function __toString(): string;
}

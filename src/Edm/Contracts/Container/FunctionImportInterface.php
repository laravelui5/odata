<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Container;

use LaravelUi5\OData\Edm\Contracts\AnnotatableInterface;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionInterface;
use LaravelUi5\OData\Edm\Contracts\NamedElementInterface;

/**
 * A function import — a container-level entry point that exposes an
 * unbound function overload as an addressable resource.
 *
 * Function imports are the mechanism by which unbound functions become
 * callable from the service root. A function import references a
 * function defined in a schema and optionally declares which entity
 * set in the container holds its return entities.
 *
 * Note: only unbound functions may be exposed as function imports.
 * Bound functions are invoked directly via the entity set or singleton
 * they are bound to and do not appear here.
 *
 * @see OData CSDL XML v4.01 §13.6 (Function Import)
 */
interface FunctionImportInterface extends NamedElementInterface, AnnotatableInterface, AnnotationTargetInterface
{
    /**
     * The function overload this import exposes.
     *
     * Always an unbound overload — bound functions are not importable.
     */
    public function getFunction(): FunctionInterface;

    /**
     * The entity set whose instances are returned by this function
     * import, expressed as the simple name of the entity set within
     * the same container, or null when the function does not return
     * entities or the entity set is not statically known.
     *
     * This is a string rather than a resolved EntitySetInterface
     * because the spec allows this value to be a path expression
     * evaluated at runtime. For static resolution during query
     * planning, resolve this name against the parent container.
     *
     * @see OData CSDL XML v4.01 §13.6
     */
    public function getEntitySet(): ?string;

    /**
     * Whether this function import is included in the service document.
     * Defaults to false when absent in CSDL.
     *
     * @see OData CSDL XML v4.01 §13.6
     */
    public function isIncludedInServiceDocument(): bool;
}

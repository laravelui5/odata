<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Container;

use LaravelUi5\OData\Edm\Contracts\AnnotatableInterface;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\NamedElementInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;

/**
 * An entity set — a named, addressable collection of entity instances
 * within an entity container.
 *
 * Entity sets are the primary runtime collections exposed by an OData
 * service. Each entity set is typed by a single entity type and may
 * declare navigation property bindings that resolve navigation targets
 * to other entity sets or singletons within the container.
 *
 * @see OData CSDL XML v4.01 §13.2 (Entity Set)
 */
interface EntitySetInterface extends NamedElementInterface, AnnotatableInterface, AnnotationTargetInterface
{
    /**
     * The entity type of the elements in this set.
     */
    public function getEntityType(): EntityTypeInterface;

    /**
     * Whether this entity set is included in the service document.
     * Defaults to true when absent in CSDL.
     */
    public function isIncludedInServiceDocument(): bool;

    /**
     * Navigation property bindings declared on this entity set.
     *
     * Each binding maps a navigation property path to a target entity
     * set or singleton within the same container. The path may include
     * type-cast segments for polymorphic navigation scenarios.
     *
     * Returns an empty array when no bindings are declared.
     *
     * @return list<NavigationPropertyBindingInterface>
     * @see OData CSDL XML v4.01 §13.4
     */
    public function getNavigationPropertyBindings(): array;

    /**
     * Returns the navigation property binding for the given path,
     * or null when no binding for that path is declared.
     */
    public function getNavigationPropertyBinding(string $path): ?NavigationPropertyBindingInterface;
}

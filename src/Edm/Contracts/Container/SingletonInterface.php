<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Container;

use LaravelUi5\OData\Edm\Contracts\AnnotatableInterface;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\NamedElementInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;

/**
 * A singleton — a named, addressable single entity instance within
 * an entity container.
 *
 * Unlike entity sets, singletons always refer to exactly one entity
 * instance. They are used for concepts like "Me" (the current user)
 * or "DefaultSettings". Singletons carry navigation property bindings
 * in the same way entity sets do.
 *
 * @see OData CSDL XML v4.01 §13.3 (Singleton)
 */
interface SingletonInterface extends NamedElementInterface, AnnotatableInterface, AnnotationTargetInterface
{
    /**
     * The entity type of this singleton's instance.
     */
    public function getEntityType(): EntityTypeInterface;

    /**
     * Navigation property bindings declared on this singleton.
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

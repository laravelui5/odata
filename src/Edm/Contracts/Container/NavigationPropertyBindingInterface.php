<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Container;

use LaravelUi5\OData\Edm\Contracts\AnnotatableInterface;

/**
 * A navigation property binding declared on an entity set or singleton.
 *
 * Bindings resolve the target of a navigation property to a concrete
 * entity set or singleton within the same entity container. The path
 * may include type-cast segments for polymorphic navigation, e.g.
 * "Items/MyService.SpecialItem".
 *
 * Bindings are annotatable per the CSDL specification.
 *
 * @see OData CSDL XML v4.01 §13.4 (Navigation Property Binding)
 */
interface NavigationPropertyBindingInterface extends AnnotatableInterface
{
    /**
     * The navigation property path this binding applies to.
     *
     * For simple cases this is just the navigation property name,
     * e.g. "Orders". For polymorphic scenarios it may contain
     * type-cast segments, e.g. "Items/MyService.SpecialItem".
     *
     * @see OData CSDL XML v4.01 §13.4.1
     */
    public function getPath(): string;

    /**
     * The qualified path to the binding target within the container,
     * e.g. "MyService.Container/Orders" or simply "Orders" when the
     * target is in the same container.
     *
     * @see OData CSDL XML v4.01 §13.4.2
     */
    public function getTarget(): string;
}

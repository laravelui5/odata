<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Property;

use LaravelUi5\OData\Edm\Contracts\AnnotatableInterface;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\NamedElementInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeFacetsInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeInterface;

/**
 * A structural property of an EntityType or ComplexType.
 *
 * Structural properties hold data values. Their type is always a
 * primitive, complex, enum, or type-definition — never an entity type.
 * The property carries its type as a fully resolved TypeInterface
 * object; the associated facets further constrain the value space.
 *
 * @see OData CSDL XML v4.01 §7 (Structural Property)
 */
interface PropertyInterface extends NamedElementInterface, AnnotatableInterface, AnnotationTargetInterface
{
    /**
     * The resolved type of this property.
     *
     * For collection-valued properties this is the type of each
     * individual element; use isCollection() to distinguish.
     *
     * @see OData CSDL XML v4.01 §7.1
     */
    public function getType(): TypeInterface;

    /**
     * Whether this property holds a collection of values rather
     * than a single value.
     */
    public function isCollection(): bool;

    /**
     * The facets constraining this property's type, or null when
     * no facets are declared beyond the type's own defaults.
     *
     * @see OData CSDL XML v4.01 §7.2
     */
    public function getFacets(): ?TypeFacetsInterface;

    /**
     * The default value as declared in CSDL, represented as a string
     * in the property type's literal format. Null when absent.
     *
     * @see OData CSDL XML v4.01 §7.2.7
     */
    public function getDefaultValue(): ?string;
}

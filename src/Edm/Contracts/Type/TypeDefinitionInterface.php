<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Type;

use LaravelUi5\OData\Edm\Contracts\AnnotatableInterface;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\EdmPrimitiveType;

/**
 * A named type definition, aliasing a primitive type with optional
 * facet constraints.
 *
 * TypeDefinitions allow a domain to introduce named types like
 * "PhoneNumber" or "ISOCurrencyCode" that are structurally Edm.String
 * but semantically distinct. Facets declared on the definition refine
 * the underlying type's value space.
 *
 * @see OData CSDL XML v4.01 §11 (Type Definition)
 */
interface TypeDefinitionInterface extends TypeInterface, AnnotatableInterface, AnnotationTargetInterface
{
    /**
     * The primitive type this definition is based on.
     *
     * @see OData CSDL XML v4.01 §11.1
     */
    public function getUnderlyingType(): EdmPrimitiveType;

    /**
     * The facets constraining the underlying primitive type,
     * or null if no facets are declared.
     */
    public function getFacets(): ?TypeFacetsInterface;
}

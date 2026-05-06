<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Type;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Contracts\Type\TypeDefinitionInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeFacetsInterface;
use LaravelUi5\OData\Edm\HasAnnotations;

final readonly class TypeDefinition implements TypeDefinitionInterface
{
    use HasAnnotations;

    /**
     * @param list<AnnotationInterface> $annotations
     */
    public function __construct(
        private string           $namespace,
        private string           $name,
        private EdmPrimitiveType $underlyingType,
        private ?TypeFacetsInterface $facets = null,
        array                    $annotations = [],
    ) {
        $this->annotations = $annotations;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQualifiedName(): string
    {
        return $this->namespace . '.' . $this->name;
    }

    public function getUnderlyingType(): EdmPrimitiveType
    {
        return $this->underlyingType;
    }

    public function getFacets(): ?TypeFacetsInterface
    {
        return $this->facets;
    }
}

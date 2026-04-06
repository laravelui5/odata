<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Property;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeFacetsInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeInterface;
use LaravelUi5\OData\Edm\HasAnnotations;

final readonly class Property implements PropertyInterface
{
    use HasAnnotations;

    /**
     * @param list<AnnotationInterface> $annotations
     */
    public function __construct(
        private string               $name,
        private TypeInterface        $type,
        private bool                 $isCollection  = false,
        private ?TypeFacetsInterface $facets        = null,
        private ?string              $defaultValue  = null,
        array                        $annotations   = [],
    ) {
        $this->annotations = $annotations;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): TypeInterface
    {
        return $this->type;
    }

    public function isCollection(): bool
    {
        return $this->isCollection;
    }

    public function getFacets(): ?TypeFacetsInterface
    {
        return $this->facets;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }
}

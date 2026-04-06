<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionParameterInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeFacetsInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeInterface;

final readonly class FunctionParameter implements FunctionParameterInterface
{
    use HasAnnotations;

    /**
     * @param list<AnnotationInterface> $annotations
     */
    public function __construct(
        private string               $name,
        private TypeInterface        $type,
        private bool                 $isCollection = false,
        private bool                 $isNullable   = true,
        private ?TypeFacetsInterface $facets       = null,
        array                        $annotations  = [],
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

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    public function getFacets(): ?TypeFacetsInterface
    {
        return $this->facets;
    }
}

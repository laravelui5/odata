<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Container;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\FunctionImportInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionInterface;
use LaravelUi5\OData\Edm\HasAnnotations;

final readonly class FunctionImport implements FunctionImportInterface
{
    use HasAnnotations;

    /**
     * @param list<AnnotationInterface> $annotations
     */
    public function __construct(
        private string            $name,
        private FunctionInterface $function,
        private ?string           $entitySet                 = null,
        private bool              $includedInServiceDocument = false,
        array                     $annotations               = [],
    ) {
        $this->annotations = $annotations;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFunction(): FunctionInterface
    {
        return $this->function;
    }

    public function getEntitySet(): ?string
    {
        return $this->entitySet;
    }

    public function isIncludedInServiceDocument(): bool
    {
        return $this->includedInServiceDocument;
    }
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Container;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Container\FunctionImportInterface;
use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;
use LaravelUi5\OData\Edm\HasAnnotations;

final readonly class EntityContainer implements EntityContainerInterface
{
    use HasAnnotations;

    /**
     * @param list<EntitySetInterface>      $entitySets
     * @param list<SingletonInterface>      $singletons
     * @param list<FunctionImportInterface> $functionImports
     * @param list<AnnotationInterface> $annotations
     */
    public function __construct(
        private string  $name,
        private array   $entitySets      = [],
        private array   $singletons      = [],
        private array   $functionImports = [],
        private ?string $extendsName     = null,
        array           $annotations     = [],
    ) {
        $this->annotations = $annotations;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEntitySets(): array
    {
        return $this->entitySets;
    }

    public function getEntitySet(string $name): ?EntitySetInterface
    {
        foreach ($this->entitySets as $entitySet) {
            if ($entitySet->getName() === $name) {
                return $entitySet;
            }
        }
        return null;
    }

    public function getSingletons(): array
    {
        return $this->singletons;
    }

    public function getSingleton(string $name): ?SingletonInterface
    {
        foreach ($this->singletons as $singleton) {
            if ($singleton->getName() === $name) {
                return $singleton;
            }
        }
        return null;
    }

    public function getFunctionImports(): array
    {
        return $this->functionImports;
    }

    public function getFunctionImport(string $name): ?FunctionImportInterface
    {
        foreach ($this->functionImports as $import) {
            if ($import->getName() === $name) {
                return $import;
            }
        }
        return null;
    }

    public function getExtendsName(): ?string
    {
        return $this->extendsName;
    }
}

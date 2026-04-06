<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Container;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\NavigationPropertyBindingInterface;
use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\HasAnnotations;

final readonly class Singleton implements SingletonInterface
{
    use HasAnnotations;

    /**
     * @param list<NavigationPropertyBindingInterface> $navigationPropertyBindings
     * @param list<AnnotationInterface> $annotations
     */
    public function __construct(
        private string              $name,
        private EntityTypeInterface $entityType,
        private array               $navigationPropertyBindings = [],
        array                       $annotations                = [],
    ) {
        $this->annotations = $annotations;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEntityType(): EntityTypeInterface
    {
        return $this->entityType;
    }

    public function getNavigationPropertyBindings(): array
    {
        return $this->navigationPropertyBindings;
    }

    public function getNavigationPropertyBinding(string $path): ?NavigationPropertyBindingInterface
    {
        foreach ($this->navigationPropertyBindings as $binding) {
            if ($binding->getPath() === $path) {
                return $binding;
            }
        }
        return null;
    }
}

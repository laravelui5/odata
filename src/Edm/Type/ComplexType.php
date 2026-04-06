<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Type;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Type\ComplexTypeInterface;
use LaravelUi5\OData\Edm\HasAnnotations;

final readonly class ComplexType implements ComplexTypeInterface
{
    use HasAnnotations;

    /**
     * @param list<PropertyInterface>           $declaredProperties
     * @param list<NavigationPropertyInterface> $declaredNavigationProperties
     * @param list<AnnotationInterface> $annotations
     */
    public function __construct(
        private string               $namespace,
        private string               $name,
        private ?ComplexTypeInterface $baseType                   = null,
        private bool                 $isAbstract                 = false,
        private bool                 $isOpen                     = false,
        private array                $declaredProperties         = [],
        private array                $declaredNavigationProperties = [],
        array                        $annotations                = [],
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

    public function getBaseType(): ?ComplexTypeInterface
    {
        return $this->baseType;
    }

    public function isAbstract(): bool
    {
        return $this->isAbstract;
    }

    public function isOpen(): bool
    {
        return $this->isOpen;
    }

    public function getDeclaredProperties(): array
    {
        return $this->declaredProperties;
    }

    public function getProperty(string $name): ?PropertyInterface
    {
        foreach ($this->declaredProperties as $property) {
            if ($property->getName() === $name) {
                return $property;
            }
        }
        return $this->baseType?->getProperty($name);
    }

    public function getDeclaredNavigationProperties(): array
    {
        return $this->declaredNavigationProperties;
    }

    public function getNavigationProperty(string $name): ?NavigationPropertyInterface
    {
        foreach ($this->declaredNavigationProperties as $navProp) {
            if ($navProp->getName() === $name) {
                return $navProp;
            }
        }
        return $this->baseType?->getNavigationProperty($name);
    }
}

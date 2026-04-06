<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Property;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\HasAnnotations;

final readonly class NavigationProperty implements NavigationPropertyInterface
{
    use HasAnnotations;

    /**
     * @param array<string, string> $referentialConstraints dependent → principal property names
     * @param list<AnnotationInterface> $annotations
     */
    public function __construct(
        private string              $name,
        private EntityTypeInterface $targetType,
        private bool                $isCollection           = true,
        private bool                $isNullable             = true,
        private ?string             $partnerName            = null,
        private bool                $isContainmentTarget    = false,
        private array               $referentialConstraints = [],
        private ?string             $onDeleteAction         = null,
        array                       $annotations            = [],
    ) {
        $this->annotations = $annotations;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTargetType(): EntityTypeInterface
    {
        return $this->targetType;
    }

    public function isCollection(): bool
    {
        return $this->isCollection;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    public function getPartnerName(): ?string
    {
        return $this->partnerName;
    }

    public function isContainmentTarget(): bool
    {
        return $this->isContainmentTarget;
    }

    public function getReferentialConstraints(): array
    {
        return $this->referentialConstraints;
    }

    public function getOnDeleteAction(): ?string
    {
        return $this->onDeleteAction;
    }
}

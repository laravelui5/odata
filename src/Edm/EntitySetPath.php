<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm;

use LaravelUi5\OData\Edm\Contracts\EntitySetPathInterface;

final readonly class EntitySetPath implements EntitySetPathInterface
{
    public function __construct(
        private string $bindingParameterName,
        private string $navigationPropertyName,
    ) {}

    public function getBindingParameterName(): string
    {
        return $this->bindingParameterName;
    }

    public function getNavigationPropertyName(): string
    {
        return $this->navigationPropertyName;
    }

    public function __toString(): string
    {
        return $this->bindingParameterName . '/' . $this->navigationPropertyName;
    }
}

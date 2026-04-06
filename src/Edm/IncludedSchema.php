<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm;

use LaravelUi5\OData\Edm\Contracts\IncludedSchemaInterface;

final readonly class IncludedSchema implements IncludedSchemaInterface
{
    public function __construct(
        private string  $namespace,
        private ?string $alias = null,
    ) {}

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }
}

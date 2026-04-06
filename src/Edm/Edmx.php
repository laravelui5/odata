<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm;

use LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface;
use LaravelUi5\OData\Edm\Contracts\EdmxInterface;
use LaravelUi5\OData\Edm\Contracts\ReferenceInterface;
use LaravelUi5\OData\Edm\Contracts\SchemaInterface;

final readonly class Edmx implements EdmxInterface
{
    /**
     * @param list<ReferenceInterface>          $references
     * @param array<string, SchemaInterface>    $schemas     keyed by namespace
     */
    public function __construct(
        private string                  $version,
        private array                   $references,
        private array                   $schemas,
        private EntityContainerInterface $entityContainer,
    ) {}

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getReferences(): array
    {
        return $this->references;
    }

    public function getReference(string $uri): ?ReferenceInterface
    {
        foreach ($this->references as $reference) {
            if ($reference->getUri() === $uri) {
                return $reference;
            }
        }
        return null;
    }

    public function getSchemas(): array
    {
        return $this->schemas;
    }

    public function getSchema(string $namespace): ?SchemaInterface
    {
        return $this->schemas[$namespace] ?? null;
    }

    public function getEntityContainer(): EntityContainerInterface
    {
        return $this->entityContainer;
    }
}

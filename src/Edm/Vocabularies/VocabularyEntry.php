<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Vocabularies;

use LaravelUi5\OData\Edm\Contracts\ReferenceInterface;
use LaravelUi5\OData\Edm\IncludedSchema;
use LaravelUi5\OData\Edm\Reference;

/**
 * A single vocabulary entry in the build-time catalog.
 *
 * Carries everything the generator needs to fetch, parse, and emit
 * PHP classes for one vocabulary: the remote XML source, the OData
 * namespace and canonical alias, and the target PHP namespace under
 * which the generated classes will be placed.
 *
 * @see VocabularyEntryInterface
 */
final readonly class VocabularyEntry implements VocabularyEntryInterface
{
    /**
     * @param list<string> $dependencies Aliases of vocabularies this one depends on, e.g. ['Core', 'Validation']
     */
    public function __construct(
        private string $namespace,
        private string $alias,
        private string $uri,
        private string $phpNamespace,
        private array  $dependencies,
    ) {}

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getPhpNamespace(): string
    {
        return $this->phpNamespace;
    }

    /** @return list<string> */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function toReference(): ReferenceInterface
    {
        return new Reference(
            uri:      $this->uri,
            includes: [new IncludedSchema(namespace: $this->namespace, alias: $this->alias)],
        );
    }
}

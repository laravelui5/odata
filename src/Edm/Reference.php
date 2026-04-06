<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\IncludedSchemaInterface;
use LaravelUi5\OData\Edm\Contracts\ReferenceInterface;

final readonly class Reference implements ReferenceInterface
{
    use HasAnnotations;

    /**
     * @param list<IncludedSchemaInterface> $includes
     * @param list<AnnotationInterface>     $annotations
     */
    public function __construct(
        private string $uri,
        private array  $includes    = [],
        array          $annotations = [],
    ) {
        $this->annotations = $annotations;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getIncludes(): array
    {
        return $this->includes;
    }

    public function getInclude(string $namespace): ?IncludedSchemaInterface
    {
        foreach ($this->includes as $include) {
            if ($include->getNamespace() === $namespace) {
                return $include;
            }
        }
        return null;
    }

    /**
     * Matches by fully qualified term name only. Alias resolution is not
     * performed because Reference has no back-pointer to the enclosing
     * EdmxInterface, and annotations on <edmx:Reference> elements are
     * practically never present in real-world documents.
     */
    public function getAnnotation(string $term, ?string $qualifier = null): ?AnnotationInterface
    {
        foreach ($this->annotations as $annotation) {
            if ($annotation->getTerm() === $term && $annotation->getQualifier() === $qualifier) {
                return $annotation;
            }
        }
        return null;
    }
}

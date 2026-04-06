<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Vocabularies\VocabularyRegistry;

/**
 * Shared implementation of AnnotatableInterface for readonly Edm classes.
 *
 * This trait serves as the canonical AnnotatableTrait described in the
 * architectural specification. It provides both getAnnotations() and the
 * alias-resolving getAnnotation() method so that callers may pass either
 * a fully qualified term name ("Org.OData.Core.V1.Description") or an
 * alias-qualified term name ("Core.Description") and receive the correct
 * result.
 *
 * Alias resolution is performed via VocabularyRegistry::getInstance(), the
 * static singleton built from VocabularyCatalog::default(). When the input
 * term is already fully qualified the singleton returns it unchanged; when
 * it carries an unknown alias the singleton returns null and the lookup
 * falls through to null.
 *
 * Every class using this trait MUST assign $this->annotations in its
 * constructor before the property is read.
 */
trait HasAnnotations
{
    /** @var list<AnnotationInterface> */
    private readonly array $annotations;

    /** @return list<AnnotationInterface> */
    public function getAnnotations(): array
    {
        return $this->annotations;
    }

    public function getAnnotation(string $term, ?string $qualifier = null): ?AnnotationInterface
    {
        $resolved = VocabularyRegistry::getInstance()->fullyQualify($term) ?? $term;

        foreach ($this->annotations as $annotation) {
            if ($annotation->getTerm() === $resolved && $annotation->getQualifier() === $qualifier) {
                return $annotation;
            }
        }
        return null;
    }
}

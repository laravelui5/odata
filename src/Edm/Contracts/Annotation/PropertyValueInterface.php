<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Annotation;

/**
 * A single named property within a record annotation value.
 *
 * Each property has a name that corresponds to a property defined
 * on the term's record type, and a value that is itself an
 * AnnotationValueInterface — which may be a constant, a nested
 * record, or a collection.
 *
 * @see OData CSDL XML v4.01 §14.4.12 (Record)
 */
interface PropertyValueInterface
{
    /**
     * The unqualified name of this property as declared on the
     * term's record type, e.g. "CollectionPath" or "Parameters".
     */
    public function getProperty(): string;

    /**
     * The value assigned to this property.
     */
    public function getValue(): AnnotationValueInterface;
}

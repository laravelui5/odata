<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;

/**
 * Contract for every model element that may carry annotations.
 *
 * In CSDL virtually every construct — types, properties, container
 * members, parameters, the schema itself — is annotatable. This
 * interface is mixed in wherever the spec permits annotations.
 *
 * The annotation representation is intentionally simplified at this
 * stage and will be refined collaboratively once the structural
 * interfaces are complete.
 *
 * Term names in annotations are always stored in fully qualified form,
 *  e.g. "com.sap.vocabularies.UI.v1.LineItem" rather than "UI.LineItem".
 *  The builder is responsible for resolving aliases to their full namespace
 *  before storing. Implementations of getAnnotation() must accept both
 *  alias-qualified and fully qualified term names as input and resolve
 *  aliases against the schema's declared namespace map before matching.
 *
 * @see OData CSDL XML v4.01 §14.2 (Annotation)
 */
interface AnnotatableInterface
{
    /**
     * All annotations applied to this model element, keyed by
     * the qualified term name, e.g. "Org.OData.Core.V1.Description".
     *
     * Returns an empty array when no annotations are present.
     *
     * @return list<AnnotationInterface>
     */
    public function getAnnotations(): array;

    /**
     * Returns the first annotation for the given term name, optionally
     * filtered by qualifier. Returns null when no matching annotation
     * is present.
     *
     * Accepts both fully qualified term names
     *  ("com.sap.vocabularies.UI.v1.LineItem") and alias-qualified names
     *  ("UI.LineItem"). Implementations must resolve alias-qualified names
     *  against the alias map derived from the enclosing schema's references
     *  before matching. Internally, annotations are always stored under
     *  their fully qualified term name.
     */
    public function getAnnotation(string $term, ?string $qualifier = null): ?AnnotationInterface;
}

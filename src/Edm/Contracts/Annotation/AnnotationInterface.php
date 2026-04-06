<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Annotation;

/**
 * A single annotation applied to a model element.
 *
 * An annotation binds a term to a value and applies that binding to
 * a specific model element. The term is identified by its qualified
 * name. An optional qualifier allows multiple annotations of the same
 * term on the same element to coexist, disambiguated by context —
 * e.g. a label for a "desktop" qualifier vs. a "mobile" qualifier.
 *
 * The value may be absent for terms whose type is implicitly Boolean
 * and whose presence alone carries the meaning (marker annotations).
 *
 * Example — a simple string annotation:
 *
 *   getTerm()      → "Core.Description"
 *   getQualifier() → null
 *   getValue()     → ConstantAnnotationValueInterface { kind: "String",
 *                      value: "The list of open orders" }
 *
 * Example — a record annotation with qualifier:
 *
 *   getTerm()      → "UI.LineItem"
 *   getQualifier() → "Expanded"
 *   getValue()     → CollectionAnnotationValueInterface { items: [...] }
 *
 * @see OData CSDL XML v4.01 §14.2 (Annotation)
 */
interface AnnotationInterface
{
    /**
     * The qualified name of the term this annotation applies,
     * e.g. "Core.Description" or "UI.LineItem".
     *
     * The name uses the vocabulary alias as it appears in the CSDL
     * document, not necessarily the fully qualified namespace form.
     *
     * @see OData CSDL XML v4.01 §14.2
     */
    public function getTerm(): string;

    /**
     * The optional qualifier that disambiguates multiple annotations
     * of the same term on the same element. Null when absent.
     *
     * @see OData CSDL XML v4.01 §14.2.1
     */
    public function getQualifier(): ?string;

    /**
     * The value of this annotation, or null for marker annotations
     * whose presence alone is significant.
     */
    public function getValue(): ?AnnotationValueInterface;
}

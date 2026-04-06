<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Annotation;

/**
 * A record annotation value — a structured value composed of named
 * property values, optionally typed by a qualified term record type.
 *
 * Records are the backbone of complex annotations. A UI.DataField
 * record, a Capabilities.FilterRestrictionsType record, or a
 * Common.ValueListType record are all represented by this interface.
 *
 * The type name is the qualified name of the record type as declared
 * in the vocabulary, e.g. "UI.DataField" or
 * "Capabilities.FilterRestrictionsType". It is absent for untyped
 * records, though well-formed SAP vocabulary annotations are
 * typically typed.
 *
 * @see OData CSDL XML v4.01 §14.4.12 (Record)
 */
interface RecordAnnotationValueInterface extends AnnotationValueInterface
{
    /**
     * The qualified name of this record's type, or null when the
     * record is untyped.
     *
     * Corresponds to the Type attribute on the <Record> element in
     * CSDL XML, e.g. "UI.DataField".
     */
    public function getType(): ?string;

    /**
     * All property values declared on this record, in document order.
     *
     * @return list<PropertyValueInterface>
     */
    public function getPropertyValues(): array;

    /**
     * Returns the property value with the given name, or null when
     * no property of that name is declared on this record.
     */
    public function getPropertyValue(string $name): ?PropertyValueInterface;
}

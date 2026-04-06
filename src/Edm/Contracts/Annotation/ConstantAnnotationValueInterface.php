<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Annotation;

/**
 * A constant annotation value — a single primitive or enum-member
 * literal that requires no further structural decomposition.
 *
 * The kind discriminates the concrete primitive type so that a
 * serialiser can emit the correct CSDL element or attribute, e.g.
 * Bool="true", String="...", or <EnumMember>UI.TextArrangement/TextOnly
 * </EnumMember>.
 *
 * Path-like constant expressions (AnnotationPath, NavigationPropertyPath,
 * PropertyPath, ValuePath) are also represented here because they are
 * serialised as opaque string literals from the serialiser's perspective.
 * The kind value identifies which path variant they are.
 *
 * @see OData CSDL XML v4.01 §14.3 (Constant Expression)
 * @see OData CSDL XML v4.01 §14.4.1 (Path Expressions)
 */
interface ConstantAnnotationValueInterface extends AnnotationValueInterface
{
    /**
     * Discriminator for the concrete primitive kind.
     *
     * Valid values mirror the CSDL constant expression element names:
     * "Binary", "Boolean", "Date", "DateTimeOffset", "Decimal",
     * "Duration", "EnumMember", "Float", "Guid", "Integer", "String",
     * "TimeOfDay", "AnnotationPath", "NavigationPropertyPath",
     * "PropertyPath", "ValuePath".
     */
    public function getKind(): string;

    /**
     * The value serialised as a string in the type's canonical format.
     *
     * For Boolean: "true" or "false".
     * For EnumMember: the qualified member name, e.g.
     *   "UI.TextArrangement/TextOnly".
     * For path expressions: the path string as it appears in CSDL.
     * For all others: the standard OData literal representation.
     */
    public function getValue(): string;
}

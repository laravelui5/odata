<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Annotation;

/**
 * Root contract for every value that can appear in an annotation.
 *
 * The value hierarchy has three concrete forms:
 *
 *   - ConstantAnnotationValueInterface  — a single primitive value
 *     (String, Bool, Int, Decimal, Float, Guid, Date, DateTimeOffset,
 *      Duration, TimeOfDay, Binary, or an EnumMember literal)
 *
 *   - RecordAnnotationValueInterface    — a structured value with
 *     named property values, optionally typed by a qualified term type
 *
 *   - CollectionAnnotationValueInterface — an ordered list of values,
 *     each of which is itself an AnnotationValueInterface
 *
 * Callers that need to act on the concrete form should use instanceof
 * checks. A future specialized layer may introduce a visitor instead.
 *
 * @see OData CSDL XML v4.01 §14.3 (Constant Expression)
 * @see OData CSDL XML v4.01 §14.4 (Dynamic Expression)
 */
interface AnnotationValueInterface
{
}

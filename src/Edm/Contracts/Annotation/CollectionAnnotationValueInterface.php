<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Annotation;

/**
 * A collection annotation value — an ordered list of annotation values.
 *
 * Collections appear wherever a term expects multiple values, e.g.
 * UI.LineItem is a collection of UI.DataField records, and
 * Capabilities.FilterRestrictions.FilterExpressionRestrictions is a
 * collection of restriction records.
 *
 * Items within a collection are heterogeneous in principle — each item
 * is an AnnotationValueInterface and may be a constant, a record, or
 * a nested collection. In practice, well-formed vocabulary annotations
 * use homogeneous collections.
 *
 * Document order is significant per the CSDL spec and is preserved.
 *
 * @see OData CSDL XML v4.01 §14.4.6 (Collection)
 * @see OData CSDL XML v4.01 §2.4 (XML Document Order)
 */
interface CollectionAnnotationValueInterface extends AnnotationValueInterface
{
    /**
     * All items in this collection, in document order.
     *
     * @return list<AnnotationValueInterface>
     */
    public function getItems(): array;

    /**
     * Whether this collection contains no items.
     */
    public function isEmpty(): bool;

    /**
     * The number of items in this collection.
     */
    public function count(): int;
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Annotation;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\CollectionAnnotationValueInterface;

/**
 * A collection annotation value — an ordered list of annotation values.
 *
 * Accepts zero or more AnnotationValueInterface instances as variadic
 * arguments. Document order is preserved.
 *
 * @see CollectionAnnotationValueInterface
 * @see OData CSDL XML v4.01 §14.4.6 (Collection)
 */
final readonly class CollectionAnnotationValue implements CollectionAnnotationValueInterface
{
    /** @var list<AnnotationValueInterface> */
    private array $items;

    public function __construct(AnnotationValueInterface ...$items)
    {
        $this->items = array_values($items);
    }

    /** @return list<AnnotationValueInterface> */
    public function getItems(): array
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function count(): int
    {
        return count($this->items);
    }
}

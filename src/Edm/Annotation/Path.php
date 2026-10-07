<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Annotation;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;

/**
 * A path expression as an annotation value: the value is read per instance from
 * the property the path names, not fixed in the annotation.
 *
 *     #[ISOCurrency(new Path('currency'))]
 *     public string $amount { … }
 *
 * serializes as `<Annotation Term="Org.OData.Measures.V1.ISOCurrency" Path="currency"/>`.
 * Every generated vocabulary term with a primitive value accepts one in place of a
 * constant — UI5 resolves currencies, units, texts and code-list scales this way.
 *
 * @see OData CSDL XML v4.01 §14.5.12 (Path)
 */
final readonly class Path
{
    public function __construct(public string $path) {}

    public function toAnnotationValue(): AnnotationValueInterface
    {
        return new ConstantAnnotationValue('Path', $this->path);
    }
}

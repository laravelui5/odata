<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Annotation;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\PropertyValueInterface;

final readonly class PropertyValue implements PropertyValueInterface
{
    public function __construct(
        private string                 $property,
        private AnnotationValueInterface $value,
    ) {}

    public function getProperty(): string
    {
        return $this->property;
    }

    public function getValue(): AnnotationValueInterface
    {
        return $this->value;
    }
}

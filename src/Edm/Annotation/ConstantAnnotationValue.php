<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Annotation;

use LaravelUi5\OData\Edm\Contracts\Annotation\ConstantAnnotationValueInterface;

final readonly class ConstantAnnotationValue implements ConstantAnnotationValueInterface
{
    public function __construct(
        private string $kind,
        private string $value,
    ) {}

    public function getKind(): string
    {
        return $this->kind;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}

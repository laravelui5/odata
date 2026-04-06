<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Annotation;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;

final readonly class Annotation implements AnnotationInterface
{
    public function __construct(
        private string                  $term,
        private ?string                 $qualifier = null,
        private ?AnnotationValueInterface $value   = null,
    ) {}

    public function getTerm(): string
    {
        return $this->term;
    }

    public function getQualifier(): ?string
    {
        return $this->qualifier;
    }

    public function getValue(): ?AnnotationValueInterface
    {
        return $this->value;
    }
}

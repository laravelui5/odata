<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Single-valued parameter
 */
final readonly class Parameter
{
    public function __construct(
        public readonly string $propertyName,
        public readonly mixed $propertyValue,
    ) {}
}

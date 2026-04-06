<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Validation\V1;

final readonly class AllowedValue
{
    public function __construct(
        public readonly mixed $value = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Validation\V1;

final readonly class ConstraintType
{
    public function __construct(
        public readonly bool $condition,
        public readonly ?string $failureMessage = null,
    ) {}
}

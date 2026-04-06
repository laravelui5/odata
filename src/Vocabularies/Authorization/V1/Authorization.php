<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Authorization\V1;

/**
 * Base type for all Authorization types
 */
final readonly class Authorization
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
    ) {}
}

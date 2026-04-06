<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Validation\V1;

/**
 * Entities related via the single- or collection-valued navigation property identified by `path` are also related via the collection-valued navigation property identified by `target`.
 */
final readonly class ItemsOfType
{
    public function __construct(
        public readonly string $path,
        public readonly string $target,
    ) {}
}

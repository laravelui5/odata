<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Analytics\V1;

/**
 * Sibling order in an [unnamed leveled hierarchy](#MultiLevelExpand)
 */
final readonly class MultiLevelExpandSiblingOrder
{
    public function __construct(
        public readonly string $property,
        public readonly ?bool $descending = null,
    ) {}
}

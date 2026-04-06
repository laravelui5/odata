<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Analytics\V1;

/**
 * Expansion state of an entry in an [unnamed leveled hierarchy](#MultiLevelExpand)
 */
final readonly class MultiLevelExpandEntry
{
    public function __construct(
        public readonly array $entry,
        public readonly ?int $levels = null,
    ) {}
}

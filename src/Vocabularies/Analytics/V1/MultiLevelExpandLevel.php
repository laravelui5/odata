<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Analytics\V1;

/**
 * Property names constituting a level in an [unnamed leveled hierarchy](#MultiLevelExpand)
 */
final readonly class MultiLevelExpandLevel
{
    public function __construct(
        public readonly array $dimensionProperties,
        public readonly array $additionalProperties,
    ) {}
}

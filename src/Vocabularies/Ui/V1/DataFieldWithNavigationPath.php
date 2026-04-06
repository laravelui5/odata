<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * A piece of data that allows navigating to related data
 */
final readonly class DataFieldWithNavigationPath
{
    public function __construct(
        public readonly mixed $value,
        public readonly string $target,
    ) {}
}

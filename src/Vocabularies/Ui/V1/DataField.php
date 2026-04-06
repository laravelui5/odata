<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * A piece of data
 */
final readonly class DataField
{
    public function __construct(
        public readonly mixed $value,
    ) {}
}

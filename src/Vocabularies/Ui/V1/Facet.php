<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Abstract base type for facets
 */
final readonly class Facet
{
    public function __construct(
        public readonly ?string $label = null,
        public readonly ?string $iD = null,
    ) {}
}

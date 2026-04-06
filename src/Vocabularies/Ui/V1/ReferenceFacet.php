<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Facet that refers to a thing perspective, e.g. LineItem
 */
final readonly class ReferenceFacet
{
    public function __construct(
        public readonly string $target,
    ) {}
}

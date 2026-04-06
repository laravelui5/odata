<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Collection of facets
 */
final readonly class CollectionFacet
{
    public function __construct(
        public readonly array $facets,
    ) {}
}

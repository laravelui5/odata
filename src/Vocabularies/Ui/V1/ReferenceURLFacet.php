<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Facet that refers to a URL
 */
final readonly class ReferenceURLFacet
{
    public function __construct(
        public readonly string $url,
        public readonly ?string $urlContentType = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

/**
 * A [Feature Object](https://datatracker.ietf.org/doc/html/rfc7946#section-3.2) represents a spatially bounded thing
 */
final readonly class GeometryFeatureType
{
    public function __construct(
        public readonly mixed $geometry = null,
        public readonly mixed $properties = null,
        public readonly ?string $id = null,
    ) {}
}

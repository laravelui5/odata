<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Properties that define a geographic location
 */
final readonly class GeoLocationType
{
    public function __construct(
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly mixed $location = null,
        public readonly mixed $address = null,
    ) {}
}

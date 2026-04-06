<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

final readonly class AddressType
{
    public function __construct(
        public readonly ?string $building = null,
        public readonly ?string $street = null,
        public readonly ?string $district = null,
        public readonly ?string $locality = null,
        public readonly ?string $region = null,
        public readonly ?string $code = null,
        public readonly ?string $country = null,
        public readonly ?string $pobox = null,
        public readonly ?string $ext = null,
        public readonly ?string $careof = null,
        public readonly ?string $label = null,
        public readonly mixed $type = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

final readonly class EmailAddressType
{
    public function __construct(
        public readonly ?string $address = null,
        public readonly mixed $type = null,
    ) {}
}

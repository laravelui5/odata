<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

final readonly class NameType
{
    public function __construct(
        public readonly ?string $surname = null,
        public readonly ?string $given = null,
        public readonly ?string $additional = null,
        public readonly ?string $prefix = null,
        public readonly ?string $suffix = null,
    ) {}
}

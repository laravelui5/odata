<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

final readonly class HeaderInfoType
{
    public function __construct(
        public readonly string $typeName,
        public readonly string $typeNamePlural,
        public readonly mixed $title = null,
        public readonly mixed $description = null,
        public readonly mixed $image = null,
        public readonly ?string $imageUrl = null,
        public readonly ?string $typeImageUrl = null,
        public readonly ?string $initials = null,
    ) {}
}

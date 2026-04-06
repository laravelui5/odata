<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

final readonly class BadgeType
{
    public function __construct(
        public readonly mixed $headLine,
        public readonly mixed $title,
        public readonly ?string $imageUrl = null,
        public readonly ?string $typeImageUrl = null,
        public readonly mixed $mainInfo = null,
        public readonly mixed $secondaryInfo = null,
    ) {}
}

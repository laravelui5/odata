<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

final readonly class SelectionPresentationVariantType
{
    public function __construct(
        public readonly mixed $selectionVariant,
        public readonly mixed $presentationVariant,
        public readonly ?string $iD = null,
        public readonly ?string $text = null,
    ) {}
}

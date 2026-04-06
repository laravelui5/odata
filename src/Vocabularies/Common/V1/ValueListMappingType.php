<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

final readonly class ValueListMappingType
{
    public function __construct(
        public readonly string $collectionPath,
        public readonly bool $distinctValuesSupported,
        public readonly array $parameters,
        public readonly ?string $label = null,
        public readonly ?int $fetchValues = null,
        public readonly ?string $presentationVariantQualifier = null,
        public readonly ?string $selectionVariantQualifier = null,
    ) {}
}

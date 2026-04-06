<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

/**
 * Exactly one of `CollectionPath` and `RelativeCollectionPath` must be provided.
 */
final readonly class ValueListType
{
    public function __construct(
        public readonly bool $distinctValuesSupported,
        public readonly bool $searchSupported,
        public readonly array $parameters,
        public readonly ?string $label = null,
        public readonly ?string $collectionPath = null,
        public readonly ?string $relativeCollectionPath = null,
        public readonly ?string $collectionRoot = null,
        public readonly ?int $fetchValues = null,
        public readonly ?string $presentationVariantQualifier = null,
        public readonly ?string $selectionVariantQualifier = null,
    ) {}
}

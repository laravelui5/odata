<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class CollectionPropertyRestrictionsType
{
    public function __construct(
        public readonly array $filterFunctions,
        public readonly bool $topSupported,
        public readonly bool $skipSupported,
        public readonly bool $insertable,
        public readonly bool $updatable,
        public readonly bool $deletable,
        public readonly ?string $collectionProperty = null,
        public readonly mixed $filterRestrictions = null,
        public readonly mixed $searchRestrictions = null,
        public readonly mixed $sortRestrictions = null,
        public readonly mixed $selectSupport = null,
    ) {}
}

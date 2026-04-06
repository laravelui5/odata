<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class NavigationPropertyRestriction
{
    public function __construct(
        public readonly string $navigationProperty,
        public readonly array $filterFunctions,
        public readonly bool $topSupported,
        public readonly bool $skipSupported,
        public readonly bool $indexableByKey,
        public readonly bool $optimisticConcurrencyControl,
        public readonly mixed $navigability = null,
        public readonly mixed $filterRestrictions = null,
        public readonly mixed $searchRestrictions = null,
        public readonly mixed $sortRestrictions = null,
        public readonly mixed $selectSupport = null,
        public readonly mixed $insertRestrictions = null,
        public readonly mixed $deepInsertSupport = null,
        public readonly mixed $updateRestrictions = null,
        public readonly mixed $deepUpdateSupport = null,
        public readonly mixed $deleteRestrictions = null,
        public readonly mixed $readRestrictions = null,
    ) {}
}

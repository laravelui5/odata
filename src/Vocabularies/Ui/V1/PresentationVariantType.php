<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

final readonly class PresentationVariantType
{
    public function __construct(
        public readonly array $sortOrder,
        public readonly array $groupBy,
        public readonly array $totalBy,
        public readonly array $total,
        public readonly array $dynamicTotal,
        public readonly bool $includeGrandTotal,
        public readonly int $initialExpansionLevel,
        public readonly array $visualizations,
        public readonly array $requestAtLeast,
        public readonly array $selectionFields,
        public readonly ?string $iD = null,
        public readonly ?string $text = null,
        public readonly ?int $maxItems = null,
        public readonly ?string $recursiveHierarchyQualifier = null,
    ) {}
}

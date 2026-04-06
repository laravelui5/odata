<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

final readonly class RecursiveHierarchyType
{
    public function __construct(
        public readonly ?string $externalNodeKeyProperty = null,
        public readonly ?string $nodeDescendantCountProperty = null,
        public readonly ?string $nodeDrillStateProperty = null,
    ) {}
}

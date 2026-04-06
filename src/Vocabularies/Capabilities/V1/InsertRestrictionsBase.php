<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class InsertRestrictionsBase
{
    public function __construct(
        public readonly bool $insertable,
        public readonly int $maxLevels,
        public readonly bool $typecastSegmentSupported,
        public readonly array $customHeaders,
        public readonly array $customQueryOptions,
        public readonly array $errorResponses,
        public readonly mixed $queryOptions = null,
        public readonly ?string $description = null,
        public readonly ?string $longDescription = null,
    ) {}
}

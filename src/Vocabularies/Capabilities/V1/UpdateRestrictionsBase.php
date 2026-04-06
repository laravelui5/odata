<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class UpdateRestrictionsBase
{
    public function __construct(
        public readonly bool $updatable,
        public readonly bool $upsertable,
        public readonly bool $deltaUpdateSupported,
        public readonly bool $filterSegmentSupported,
        public readonly bool $typecastSegmentSupported,
        public readonly int $maxLevels,
        public readonly array $customHeaders,
        public readonly array $customQueryOptions,
        public readonly array $errorResponses,
        public readonly mixed $updateMethod = null,
        public readonly ?array $permissions = null,
        public readonly mixed $queryOptions = null,
        public readonly ?string $description = null,
        public readonly ?string $longDescription = null,
    ) {}
}

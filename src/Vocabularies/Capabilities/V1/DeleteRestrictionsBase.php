<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class DeleteRestrictionsBase
{
    public function __construct(
        public readonly bool $deletable,
        public readonly int $maxLevels,
        public readonly bool $filterSegmentSupported,
        public readonly bool $typecastSegmentSupported,
        public readonly array $customHeaders,
        public readonly array $customQueryOptions,
        public readonly array $errorResponses,
        public readonly ?array $permissions = null,
        public readonly ?string $description = null,
        public readonly ?string $longDescription = null,
    ) {}
}

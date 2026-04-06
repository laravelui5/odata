<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class OperationRestrictionsType
{
    public function __construct(
        public readonly bool $filterSegmentSupported,
        public readonly array $customHeaders,
        public readonly array $customQueryOptions,
        public readonly array $errorResponses,
        public readonly ?array $permissions = null,
    ) {}
}

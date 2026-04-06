<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class ReadRestrictionsBase
{
    public function __construct(
        public readonly bool $readable,
        public readonly array $customHeaders,
        public readonly array $customQueryOptions,
        public readonly array $errorResponses,
        public readonly ?array $permissions = null,
        public readonly ?string $description = null,
        public readonly ?string $longDescription = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

final readonly class RevisionType
{
    public function __construct(
        public readonly mixed $kind,
        public readonly string $description,
        public readonly ?string $version = null,
    ) {}
}

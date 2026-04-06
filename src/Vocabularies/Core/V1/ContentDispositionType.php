<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

final readonly class ContentDispositionType
{
    public function __construct(
        public readonly string $type,
        public readonly ?string $filename = null,
    ) {}
}

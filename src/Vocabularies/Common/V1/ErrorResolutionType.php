<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

final readonly class ErrorResolutionType
{
    public function __construct(
        public readonly ?string $analysis = null,
        public readonly ?string $note = null,
        public readonly ?string $additionalNote = null,
    ) {}
}

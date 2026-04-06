<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

final readonly class KPIType
{
    public function __construct(
        public readonly mixed $selectionVariant,
        public readonly mixed $dataPoint,
        public readonly array $additionalDataPoints,
        public readonly ?string $iD = null,
        public readonly ?string $shortDescription = null,
        public readonly mixed $detail = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Reference period
 */
final readonly class ReferencePeriod
{
    public function __construct(
        public readonly ?string $description = null,
        public readonly ?string $start = null,
        public readonly ?string $end = null,
    ) {}
}

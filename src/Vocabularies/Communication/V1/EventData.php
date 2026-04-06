<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

final readonly class EventData
{
    public function __construct(
        public readonly array $categories,
        public readonly ?string $summary = null,
        public readonly ?string $description = null,
        public readonly ?string $dtstart = null,
        public readonly ?string $dtend = null,
        public readonly ?string $duration = null,
        public readonly ?string $class = null,
        public readonly ?string $status = null,
        public readonly ?string $location = null,
        public readonly ?bool $transp = null,
        public readonly ?bool $wholeday = null,
        public readonly ?string $fbtype = null,
    ) {}
}

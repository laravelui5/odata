<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

final readonly class TaskData
{
    public function __construct(
        public readonly ?string $summary = null,
        public readonly ?string $description = null,
        public readonly ?string $due = null,
        public readonly ?string $completed = null,
        public readonly ?int $percentcomplete = null,
        public readonly ?int $priority = null,
    ) {}
}

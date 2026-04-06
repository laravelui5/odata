<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

final readonly class NoteType
{
    public function __construct(
        public readonly string $content,
        public readonly string $type,
        public readonly bool $multipleNotes,
        public readonly ?string $title = null,
        public readonly ?int $maximalLength = null,
    ) {}
}

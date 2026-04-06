<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

final readonly class MediaResourceType
{
    public function __construct(
        public readonly ?string $url = null,
        public readonly mixed $stream = null,
        public readonly ?string $contentType = null,
        public readonly ?int $byteSize = null,
        public readonly ?string $changedAt = null,
        public readonly mixed $thumbnail = null,
        public readonly mixed $title = null,
        public readonly mixed $description = null,
    ) {}
}

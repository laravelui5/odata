<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Properties that describe an image
 */
final readonly class ImageType
{
    public function __construct(
        public readonly string $url,
        public readonly mixed $stream = null,
        public readonly ?string $width = null,
        public readonly ?string $height = null,
    ) {}
}

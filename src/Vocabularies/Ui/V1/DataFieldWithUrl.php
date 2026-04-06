<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * A piece of data that allows navigating to other information on the Web
 */
final readonly class DataFieldWithUrl
{
    public function __construct(
        public readonly mixed $value,
        public readonly string $url,
        public readonly ?string $urlContentType = null,
    ) {}
}

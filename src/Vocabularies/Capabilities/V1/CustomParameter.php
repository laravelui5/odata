<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

/**
 * A custom parameter is either a header or a query option
 */
final readonly class CustomParameter
{
    public function __construct(
        public readonly string $name,
        public readonly bool $required,
        public readonly array $exampleValues,
        public readonly ?string $description = null,
        public readonly ?string $documentationURL = null,
    ) {}
}

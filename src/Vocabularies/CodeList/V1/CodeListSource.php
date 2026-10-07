<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\CodeList\V1;

/**
 * An entity set containing the code list for currencies
 */
final readonly class CodeListSource
{
    public function __construct(
        public readonly string $url,
        public readonly string $collectionPath,
    ) {}
}

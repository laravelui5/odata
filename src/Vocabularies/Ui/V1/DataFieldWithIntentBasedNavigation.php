<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * A piece of data that allows triggering intent-based UI navigation
 */
final readonly class DataFieldWithIntentBasedNavigation
{
    public function __construct(
        public readonly mixed $value,
        public readonly string $semanticObject,
        public readonly array $mapping,
        public readonly ?string $action = null,
    ) {}
}

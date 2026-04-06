<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Triggers intent-based UI navigation
 */
final readonly class DataFieldForIntentBasedNavigation
{
    public function __construct(
        public readonly string $semanticObject,
        public readonly bool $navigationAvailable,
        public readonly bool $requiresContext,
        public readonly array $mapping,
        public readonly ?string $action = null,
    ) {}
}

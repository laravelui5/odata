<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

/**
 * Changes to the source properties or source entities may have side-effects on the target properties or entities.
 */
final readonly class SideEffectsType
{
    public function __construct(
        public readonly array $sourceProperties,
        public readonly array $sourceEvents,
        public readonly array $targetProperties,
        public readonly bool $discretionary,
        public readonly ?array $sourceEntities = null,
        public readonly ?array $targetEntities = null,
        public readonly ?string $triggerAction = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Elementary building block that represents a piece of data and/or allows triggering an action
 */
final readonly class DataFieldAbstract
{
    public function __construct(
        public readonly ?string $label = null,
        public readonly mixed $criticality = null,
        public readonly mixed $criticalityRepresentation = null,
        public readonly ?string $iconUrl = null,
    ) {}
}

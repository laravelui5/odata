<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Triggers an OData action
 */
final readonly class DataFieldForAction
{
    public function __construct(
        public readonly string $action,
        public readonly mixed $invocationGrouping = null,
    ) {}
}

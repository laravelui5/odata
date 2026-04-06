<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Triggers an action
 */
final readonly class DataFieldForActionAbstract
{
    public function __construct(
        public readonly bool $inline,
        public readonly bool $determining,
    ) {}
}

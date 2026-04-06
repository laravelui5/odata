<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Collection of OData actions and intent based navigations
 */
final readonly class DataFieldWithActionGroup
{
    public function __construct(
        public readonly mixed $value,
        public readonly array $actions,
    ) {}
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Collection of OData actions and intent based navigations
 */
final readonly class DataFieldForActionGroup
{
    public function __construct(
        public readonly array $actions,
        public readonly ?string $iD = null,
    ) {}
}

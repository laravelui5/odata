<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

final readonly class SelectionVariantType
{
    public function __construct(
        public readonly array $parameters,
        public readonly array $selectOptions,
        public readonly ?string $iD = null,
        public readonly ?string $text = null,
        public readonly ?string $filterExpression = null,
    ) {}
}

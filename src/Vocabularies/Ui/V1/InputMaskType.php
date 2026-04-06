<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

final readonly class InputMaskType
{
    public function __construct(
        public readonly string $mask,
        public readonly string $placeholderSymbol,
        public readonly array $rules,
    ) {}
}

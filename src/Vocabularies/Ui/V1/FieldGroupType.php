<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

final readonly class FieldGroupType
{
    public function __construct(
        public readonly array $data,
        public readonly ?string $label = null,
    ) {}
}

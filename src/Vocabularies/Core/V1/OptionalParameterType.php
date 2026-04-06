<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

final readonly class OptionalParameterType
{
    public function __construct(
        public readonly ?string $defaultValue = null,
    ) {}
}

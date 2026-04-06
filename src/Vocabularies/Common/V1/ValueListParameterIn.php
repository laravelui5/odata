<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

final readonly class ValueListParameterIn
{
    public function __construct(
        public readonly string $localDataProperty,
        public readonly bool $initialValueIsSignificant,
    ) {}
}

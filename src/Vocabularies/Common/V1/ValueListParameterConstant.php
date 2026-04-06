<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

final readonly class ValueListParameterConstant
{
    public function __construct(
        public readonly mixed $constant,
        public readonly bool $initialValueIsSignificant,
    ) {}
}

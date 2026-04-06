<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

final readonly class InputMaskRuleType
{
    public function __construct(
        public readonly string $maskSymbol,
        public readonly string $regExp,
    ) {}
}

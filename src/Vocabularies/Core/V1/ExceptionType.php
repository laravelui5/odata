<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

final readonly class ExceptionType
{
    public function __construct(
        public readonly mixed $info = null,
    ) {}
}

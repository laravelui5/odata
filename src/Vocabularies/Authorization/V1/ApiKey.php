<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Authorization\V1;

final readonly class ApiKey
{
    public function __construct(
        public readonly string $keyName,
        public readonly mixed $location,
    ) {}
}

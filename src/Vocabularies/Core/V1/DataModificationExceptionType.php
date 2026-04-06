<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

final readonly class DataModificationExceptionType
{
    public function __construct(
        public readonly mixed $failedOperation,
        public readonly ?int $responseCode = null,
    ) {}
}

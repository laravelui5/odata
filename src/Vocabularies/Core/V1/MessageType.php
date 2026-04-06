<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

final readonly class MessageType
{
    public function __construct(
        public readonly string $code,
        public readonly string $message,
        public readonly string $severity,
        public readonly array $details,
        public readonly ?string $target = null,
    ) {}
}

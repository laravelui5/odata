<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class CallbackProtocol
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $urlTemplate = null,
        public readonly ?string $documentationUrl = null,
    ) {}
}

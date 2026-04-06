<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class DeepUpdateSupportType
{
    public function __construct(
        public readonly bool $supported,
        public readonly bool $contentIDSupported,
    ) {}
}

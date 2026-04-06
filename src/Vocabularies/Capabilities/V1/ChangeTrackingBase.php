<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class ChangeTrackingBase
{
    public function __construct(
        public readonly bool $supported,
    ) {}
}

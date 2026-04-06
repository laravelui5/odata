<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

final readonly class ApplicationType
{
    public function __construct(
        public readonly ?string $component = null,
        public readonly ?string $serviceRepository = null,
        public readonly ?string $serviceId = null,
        public readonly ?string $serviceVersion = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class ReadRestrictionsType
{
    public function __construct(
        public readonly bool $typecastSegmentSupported,
        public readonly mixed $readByKeyRestrictions = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Discovery\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class ODataProperty
{
    public function __construct(
        public ?string $name = null,
        public ?string $type = null,
        public ?bool $nullable = null,
    ) {}
}

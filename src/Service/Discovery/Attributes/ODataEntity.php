<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Discovery\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class ODataEntity
{
    public function __construct(
        public ?string $name = null,
        public ?string $entitySet = null,
    ) {}
}

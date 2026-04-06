<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Discovery\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class ODataNavigation
{
    public function __construct(
        public ?string $name = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Discovery\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final readonly class ODataIgnore {}

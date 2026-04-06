<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning;

use LaravelUi5\OData\Protocol\Planning\Expression\FilterExpression;

final readonly class OrderByItem
{
    public function __construct(
        public readonly FilterExpression $expression,  // typically PropertyPathExpression
        public readonly OrderDirection   $direction,
    ) {}
}

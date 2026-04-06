<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning;

use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Protocol\Planning\Expression\FilterExpression;

final readonly class ExpandItem
{
    public function __construct(
        public readonly NavigationPropertyInterface $property,
        public readonly EntitySetInterface          $targetSet,   // resolved at plan time
        public readonly ?FilterExpression           $filter   = null,
        public readonly SelectList                  $select   = new SelectList(),
        public readonly ExpandList                  $expand   = new ExpandList(),
        public readonly ?OrderByList                $orderBy  = null,
        public readonly ?int                        $top      = null,
        public readonly ?int                        $skip     = null,
        public readonly bool                        $count    = false,
    ) {}
}

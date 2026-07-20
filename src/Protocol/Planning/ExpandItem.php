<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning;

use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Protocol\Planning\Expression\FilterExpression;

final readonly class ExpandItem
{
    public function __construct(
        public NavigationPropertyInterface $property,
        public EntitySetInterface          $targetSet,   // resolved at plan time
        public ?FilterExpression           $filter   = null,
        public SelectList                  $select   = new SelectList(),
        public ExpandList                  $expand   = new ExpandList(),
        public ?OrderByList                $orderBy  = null,
        public ?int                        $top      = null,
        public ?int                        $skip     = null,
        public bool                        $count    = false,
    ) {}
}

<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning;

use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Protocol\Planning\Expression\FilterExpression;

final readonly class EntitySetQueryPlan extends QueryPlan
{
    /**
     * @param list<ComputedProperty> $compute  Computed properties to add to each result row.
     */
    public function __construct(
        public EntitySetInterface $target,
        public ?FilterExpression  $filter,
        public SelectList         $select,
        public ExpandList         $expand,
        public OrderByList        $orderBy,
        public ?int               $top,
        public ?int               $skip,
        public ?string            $skipToken,
        public bool               $count,
        public ?string            $search    = null,
        public array              $compute   = [],
        public ?int               $maxPageSize = null,
        public ?NavigationAnchor  $anchor    = null,
    ) {}
}

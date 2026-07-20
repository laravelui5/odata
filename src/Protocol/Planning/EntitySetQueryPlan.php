<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning;

use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Http\CustomQueryOptions;
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
        public CustomQueryOptions $customQueryOptions = new CustomQueryOptions(),
    ) {}

    /** Readonly clone with a replaced expand list (used by the read-authz honest-partial prune). */
    public function withExpand(ExpandList $expand): self
    {
        return new self(
            $this->target,
            $this->filter,
            $this->select,
            $expand,
            $this->orderBy,
            $this->top,
            $this->skip,
            $this->skipToken,
            $this->count,
            $this->search,
            $this->compute,
            $this->maxPageSize,
            $this->anchor,
            $this->customQueryOptions,
        );
    }
}

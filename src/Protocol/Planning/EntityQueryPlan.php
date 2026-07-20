<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning;

use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Http\CustomQueryOptions;

final readonly class EntityQueryPlan extends QueryPlan
{
    public function __construct(
        public EntitySetInterface $target,
        public KeyExpression      $key,
        public SelectList         $select,
        public ExpandList         $expand,
        public ?NavigationAnchor  $anchor = null,
        public CustomQueryOptions $customQueryOptions = new CustomQueryOptions(),
    ) {}

    /** Readonly clone with a replaced expand list (used by the read-authz honest-partial prune). */
    public function withExpand(ExpandList $expand): self
    {
        return new self(
            $this->target,
            $this->key,
            $this->select,
            $expand,
            $this->anchor,
            $this->customQueryOptions,
        );
    }
}
